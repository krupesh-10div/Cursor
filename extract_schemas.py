#!/usr/bin/env python3
import csv
import json
import sys
import traceback
from typing import Any, Dict, Iterable, List, Optional, Tuple

import requests
from bs4 import BeautifulSoup


PRODUCT_URLS: List[str] = [
    "https://softicated.com/produit/tabouret-vert-foret/",
    "https://softicated.com/produit/tabouret-design-turquoise-softicated/",
    "https://softicated.com/produit/tabouret-rouge-signet-ring/",
    "https://softicated.com/produit/tabouret-rose-signet-ring/",
    "https://softicated.com/produit/tabouret-bleu-marine-signet-ring/",
    "https://softicated.com/produit/tabouret-signet-ring-marron/",
    "https://softicated.com/produit/tabouret-lilas/",
    "https://softicated.com/produit/tabouret-gris/",
    "https://softicated.com/produit/tabouret-cuivre/",
    "https://softicated.com/produit/tabouret-dore/",
    "https://softicated.com/produit/tabouret-blanc/",
    "https://softicated.com/produit/tabouret-noir/",
]


def iter_products(node: Any) -> Iterable[Dict[str, Any]]:
    """Recursively yield objects with @type == Product from arbitrary JSON-LD structures."""
    if isinstance(node, dict):
        node_type = node.get("@type")
        if node_type is not None:
            if isinstance(node_type, list) and any(t == "Product" for t in node_type):
                yield node
            elif isinstance(node_type, str) and node_type == "Product":
                yield node

        # Handle @graph container
        if "@graph" in node and isinstance(node["@graph"], list):
            for child in node["@graph"]:
                yield from iter_products(child)

        # Recurse into values
        for value in node.values():
            if isinstance(value, (dict, list)):
                yield from iter_products(value)

    elif isinstance(node, list):
        for item in node:
            yield from iter_products(item)


def select_best_product(products: List[Dict[str, Any]]) -> Optional[Dict[str, Any]]:
    """Heuristically select the most relevant Product object when multiple are present."""
    if not products:
        return None

    # Prefer ones with offers
    with_offers = [p for p in products if isinstance(p.get("offers"), (dict, list))]
    if with_offers:
        return with_offers[0]

    # Fallback to the first
    return products[0]


def _get_meta(soup: BeautifulSoup, key: str, value: str) -> Optional[str]:
    tag = soup.find("meta", attrs={key: value})
    if tag is None:
        return None
    return tag.get("content")


def _extract_price_and_currency_from_meta(soup: BeautifulSoup) -> Tuple[Optional[str], Optional[str]]:
    currency = _get_meta(soup, "property", "product:price:currency")
    amount = _get_meta(soup, "property", "product:price:amount")
    return amount, currency


def _extract_price_and_currency_from_html(soup: BeautifulSoup) -> Tuple[Optional[str], Optional[str]]:
    # WooCommerce typical structure: <span class="woocommerce-Price-amount amount"><bdi>960 <span class="woocommerce-Price-currencySymbol">CHF</span></bdi></span>
    price_container = soup.select_one(".price .woocommerce-Price-amount")
    if not price_container:
        return None, None
    bdi = price_container.find("bdi")
    if not bdi:
        text = price_container.get_text(" ", strip=True)
    else:
        text = bdi.get_text(" ", strip=True)

    # Extract numeric and currency parts
    # Example: "960 CHF" or "995 CHF"
    parts = text.replace("\xa0", " ").split()
    amount = None
    currency = None
    for part in parts:
        if part.replace(",", ".").replace(".", "").isdigit():
            # keep the original for amount but normalize comma decimal
            amount = part.replace("\xa0", "").replace(",", ".")
        elif len(part) in (3, 4) and part.isalpha():
            currency = part
    return amount, currency


def _extract_from_ga4w(html: str) -> Tuple[Optional[str], Optional[str], Optional[str]]:
    # Pull sku(identifier), price and currency from the GA4 data block if present
    # Examples seen:
    # currency_code":"CHF"
    # "prices":{"price":960,
    # "identifier":"B10012"
    import re

    currency = None
    amount = None
    sku = None

    m_currency = re.search(r'currency_code"\s*:\s*"([A-Z]{3})"', html)
    if m_currency:
        currency = m_currency.group(1)

    m_price = re.search(r'"prices"\s*:\s*\{\s*"price"\s*:\s*([0-9]+(?:\.[0-9]+)?)', html)
    if m_price:
        amount = m_price.group(1)

    m_sku = re.search(r'"identifier"\s*:\s*"([A-Za-z0-9_-]+)"', html)
    if m_sku:
        sku = m_sku.group(1)

    return sku, amount, currency


def _extract_dimensions_material_weight(soup: BeautifulSoup) -> Tuple[Optional[str], Optional[str], Optional[str], Optional[str], Optional[str]]:
    import re
    text = soup.get_text("\n", strip=True)
    # Dimensions in forms like: "Dimensions en cm (L x l x h) : 38 x 35 x 47" or similar variants
    m_dim = re.search(r'Dimensions?[^\n]*?:\s*([0-9]+(?:[\.,][0-9]+)?)\s*[x×]\s*([0-9]+(?:[\.,][0-9]+)?)\s*[x×]\s*([0-9]+(?:[\.,][0-9]+)?)', text, re.IGNORECASE)
    width = depth = height = None
    if m_dim:
        width = m_dim.group(1).replace(",", ".")
        depth = m_dim.group(2).replace(",", ".")
        height = m_dim.group(3).replace(",", ".")

    material = None
    # Look for line starting with Matériaux
    m_mat = re.search(r'Mat[ée]riaux\s*:\s*([^\n]+)', text, re.IGNORECASE)
    if m_mat:
        material = m_mat.group(1).strip()

    weight = None
    m_weight = re.search(r'Poids\s*Net[^:]*:\s*([0-9]+(?:[\.,][0-9]+)?)', text, re.IGNORECASE)
    if m_weight:
        weight = m_weight.group(1).replace(",", ".")

    return width, depth, height, material, weight


def _detect_color_from_text(soup: BeautifulSoup) -> Optional[str]:
    text = soup.get_text(" ", strip=True)
    candidates = [
        "Vert Forêt",
        "Turquoise",
        "Rouge",
        "Rose",
        "Bleu Marine",
        "Marron",
        "Lilas",
        "Gris",
        "Cuivre",
        "Doré",
        "Blanc",
        "Noir",
    ]
    for candidate in candidates:
        if candidate in text:
            return candidate
    # Try lowercase fallback
    lower_text = text.lower()
    for candidate in candidates:
        if candidate.lower() in lower_text:
            return candidate
    return None


def _build_product_schema_from_page(url: str, soup: BeautifulSoup, html: str) -> Optional[Dict[str, Any]]:
    title = _get_meta(soup, "property", "og:title") or _get_meta(soup, "name", "og:title")
    if title:
        # Strip site suffix if present
        for sep in [" - Softicated", " – Softicated", "- Softicated", "– Softicated"]:
            if title.endswith(sep):
                title = title[: -len(sep)].strip()
                break

    description = (
        _get_meta(soup, "property", "og:description")
        or _get_meta(soup, "name", "description")
        or _get_meta(soup, "name", "twitter:description")
    )

    image = _get_meta(soup, "property", "og:image") or _get_meta(soup, "name", "og:image")

    # Price and currency
    amount, currency = _extract_price_and_currency_from_meta(soup)
    if not amount or not currency:
        html_amount, html_currency = _extract_price_and_currency_from_html(soup)
        amount = amount or html_amount
        currency = currency or html_currency

    # SKU (identifier) and fallbacks for price/currency from GA4 script
    sku, ga_price, ga_currency = _extract_from_ga4w(html)
    if not amount and ga_price:
        amount = ga_price
    if not currency and ga_currency:
        currency = ga_currency

    # Dimensions, material, weight
    width, depth, height, material, weight = _extract_dimensions_material_weight(soup)

    color = _detect_color_from_text(soup)

    if not title and not (sku or amount or currency):
        # Too little information to build a meaningful schema
        return None

    product: Dict[str, Any] = {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": title or "",
        "image": [image] if image else [],
        "description": description or "",
        "sku": sku or "",
        "brand": {"@type": "Brand", "name": "Softicated"},
        "offers": {
            "@type": "Offer",
            "url": url,
            "priceCurrency": currency or "",
            "price": amount or "",
            "availability": "https://schema.org/InStock",
            "itemCondition": "https://schema.org/NewCondition",
            "seller": {"@type": "Organization", "name": "Softicated"},
        },
        "color": color or "",
        "material": material or "",
        "weight": weight or "",
        "additionalProperty": [
            {"@type": "PropertyValue", "name": "Width", "value": f"{width} cm" if width else ""},
            {"@type": "PropertyValue", "name": "Depth", "value": f"{depth} cm" if depth else ""},
            {"@type": "PropertyValue", "name": "Height", "value": f"{height} cm" if height else ""},
            {"@type": "PropertyValue", "name": "Usage", "value": "Assise design"},
            {"@type": "PropertyValue", "name": "Origin", "value": ""},
            {"@type": "PropertyValue", "name": "Collection", "value": "Signet Ring"},
        ],
    }

    return product


def fetch_schema_for_url(url: str, timeout: int = 30) -> Optional[Dict[str, Any]]:
    headers = {
        "User-Agent": (
            "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
            "(KHTML, like Gecko) Chrome/127.0.0.0 Safari/537.36"
        )
    }
    response = requests.get(url, headers=headers, timeout=timeout)
    response.raise_for_status()

    html = response.text
    soup = BeautifulSoup(html, "html.parser")
    script_tags = soup.find_all("script", {"type": "application/ld+json"})

    products: List[Dict[str, Any]] = []

    for script_tag in script_tags:
        raw_text = script_tag.string or script_tag.get_text() or ""
        if not raw_text.strip():
            continue
        try:
            data = json.loads(raw_text)
        except json.JSONDecodeError:
            # Some sites embed multiple JSON objects without an array; attempt a lenient parse by wrapping
            # This is a best-effort; skip on failure.
            try:
                data = json.loads(raw_text.strip().rstrip(";") )
            except Exception:
                continue

        for product_obj in iter_products(data):
            products.append(product_obj)

    best = select_best_product(products)
    if best is not None:
        return best

    # Fallback: construct a Product schema from page content
    return _build_product_schema_from_page(url, soup, html)


def minify_json(data: Dict[str, Any]) -> str:
    return json.dumps(data, ensure_ascii=False, separators=(",", ":"))


def main(argv: List[str]) -> int:
    output_csv_path = "/workspace/softicated_product_schemas.csv"
    rows: List[List[str]] = []

    for url in PRODUCT_URLS:
        try:
            product_schema = fetch_schema_for_url(url)
            if product_schema is None:
                rows.append([url, ""])
            else:
                rows.append([url, minify_json(product_schema)])
        except Exception:
            traceback.print_exc()
            rows.append([url, ""])

    with open(output_csv_path, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["url", "schema"])  # header
        writer.writerows(rows)

    print(output_csv_path)
    return 0


if __name__ == "__main__":
    raise SystemExit(main(sys.argv))

