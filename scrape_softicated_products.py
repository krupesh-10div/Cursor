import csv
import json
import re
import sys
from html import unescape
from typing import List, Dict, Any, Optional, Tuple
from urllib.request import Request, urlopen
from urllib.error import URLError, HTTPError

USER_AGENT = (
    "Mozilla/5.0 (X11; Linux x86_64) AppleWebKit/537.36 "
    "(KHTML, like Gecko) Chrome/124.0.0.0 Safari/537.36"
)
TIMEOUT = 30


def fetch_html(url: str) -> str:
    req = Request(url, headers={"User-Agent": USER_AGENT, "Accept-Language": "en,fr;q=0.9"})
    with urlopen(req, timeout=TIMEOUT) as resp:
        charset = resp.headers.get_content_charset() or "utf-8"
        data = resp.read()
    return data.decode(charset, errors="replace")


def parse_meta_tags(html: str) -> List[Dict[str, str]]:
    meta_tags: List[Dict[str, str]] = []
    for tag in re.findall(r"<meta\b[^>]*>", html, re.I | re.S):
        attrs: Dict[str, str] = {}
        for k, v1, v2, v3, v4 in re.findall(
            r"([a-zA-Z_:.-]+)\s*=\s*(\"([^\"]*)\"|'([^']*)'|([^\"'>\s]+))",
            tag,
            re.I | re.S,
        ):
            value = v2 or v3 or v4 or ""
            attrs[k.lower()] = unescape(value)
        meta_tags.append(attrs)
    return meta_tags


def get_meta_first(meta_tags: List[Dict[str, str]], key: str, value: str) -> Optional[str]:
    for m in meta_tags:
        if m.get("name") == value or m.get("property") == value:
            content = m.get("content")
            if content:
                return content.strip()
    return None


def get_meta_all(meta_tags: List[Dict[str, str]], key: str, value: str) -> List[str]:
    values: List[str] = []
    for m in meta_tags:
        if m.get("name") == value or m.get("property") == value:
            content = m.get("content")
            if content:
                values.append(content.strip())
    return values


def strip_tags(text: str) -> str:
    # Remove tags and collapse whitespace
    text = re.sub(r"<script\b[\s\S]*?</script>", " ", text, flags=re.I)
    text = re.sub(r"<style\b[\s\S]*?</style>", " ", text, flags=re.I)
    text = re.sub(r"<[^>]+>", " ", text)
    text = unescape(text)
    text = re.sub(r"\s+", " ", text).strip()
    return text


def find_title(html: str, meta_tags: List[Dict[str, str]]) -> Optional[str]:
    # Prefer og:title
    t = get_meta_first(meta_tags, "property", "og:title")
    if t:
        return t
    # h1 with common classes
    m = re.search(r"<h1[^>]*class=\"[^\"]*(product_title|entry-title)[^\"]*\"[^>]*>([\s\S]*?)</h1>", html, re.I)
    if m:
        return strip_tags(m.group(2))
    # fallback title tag
    m = re.search(r"<title>([\s\S]*?)</title>", html, re.I)
    if m:
        return strip_tags(m.group(1))
    return None


def find_description(html: str, meta_tags: List[Dict[str, str]]) -> Optional[str]:
    for key in [
        ("name", "description"),
        ("property", "og:description"),
        ("name", "twitter:description"),
    ]:
        d = get_meta_first(meta_tags, key[0], key[1])
        if d:
            return d
    return None


def find_images(meta_tags: List[Dict[str, str]]) -> List[str]:
    images = get_meta_all(meta_tags, "property", "og:image")
    # Deduplicate while preserving order
    seen = set()
    out: List[str] = []
    for u in images:
        if u and u not in seen:
            seen.add(u)
            out.append(u)
    return out


def find_price_and_currency(meta_tags: List[Dict[str, str]], html: str) -> Tuple[Optional[str], Optional[str]]:
    price = get_meta_first(meta_tags, "property", "product:price:amount")
    currency = get_meta_first(meta_tags, "property", "product:price:currency")
    # Normalize price to two decimals when numeric
    if price:
        cleaned = price.replace("\xa0", " ")
        cleaned = re.sub(r"[^0-9,\. ]", "", cleaned).strip()
        if cleaned:
            temp = cleaned.replace(" ", "")
            if "," in temp and "." in temp:
                if temp.rfind(",") > temp.rfind("."):
                    temp = temp.replace(".", "").replace(",", ".")
                else:
                    temp = temp.replace(",", "")
            elif "," in temp:
                temp = temp.replace(".", "").replace(",", ".")
            else:
                temp = temp.replace(",", "")
            try:
                price = f"{float(temp):.2f}"
            except ValueError:
                pass
    if not currency:
        # Guess from symbols in text
        body_text = strip_tags(html.lower())
        if "€" in html or " eur" in body_text:
            currency = "EUR"
        elif "$" in html or " usd" in body_text:
            currency = "USD"
        elif "£" in html or " gbp" in body_text:
            currency = "GBP"
    return price, currency


def find_sku(html: str) -> Optional[str]:
    # itemprop="sku" content="..."
    m = re.search(r"itemprop=\"sku\"[^>]*content=\"([^\"]+)\"", html, re.I)
    if m:
        return m.group(1).strip()
    # <span class="sku">...</span>
    m = re.search(r"<span[^>]*class=\"[^\"]*sku[^\"]*\"[^>]*>([\s\S]*?)</span>", html, re.I)
    if m:
        return strip_tags(m.group(1))
    # Raw text 'SKU:'
    m = re.search(r"\bSKU\s*[:#]\s*([A-Za-z0-9_-]+)", html, re.I)
    if m:
        return m.group(1).strip()
    return None


def find_attribute_value(html: str, labels: List[str]) -> Optional[str]:
    # Attempt to find attribute table rows like <th>Label</th><td>Value</td>
    for label in labels:
        # th/td pattern
        m = re.search(
            rf"<tr[^>]*>\s*<t[hd][^>]*>\s*{re.escape(label)}\s*</t[hd]>\s*<t[hd][^>]*>([\s\S]*?)</t[hd]>\s*</tr>",
            html,
            re.I,
        )
        if m:
            return strip_tags(m.group(1))
        # dt/dd pattern
        m = re.search(
            rf"<dt[^>]*>\s*{re.escape(label)}\s*</dt>\s*<dd[^>]*>([\s\S]*?)</dd>",
            html,
            re.I,
        )
        if m:
            return strip_tags(m.group(1))
    return None


def _parse_material_list(material_text: str) -> List[str]:
    parts = re.split(r",|/|\band\b|\bet\b|\+", material_text, flags=re.I)
    cleaned: List[str] = []
    for p in parts:
        token = unescape(p).strip()
        if not token:
            continue
        # Normalize common French terms
        lower = token.lower()
        if lower in {"laine"}:
            token = "Wool"
        elif "soie" in lower or "viscose" in lower:
            token = "Botanical Silk"
        cleaned.append(token)
    # Deduplicate preserving order
    seen = set()
    out: List[str] = []
    for c in cleaned:
        if c not in seen:
            seen.add(c)
            out.append(c)
    return out


def _cm_to_m_value(cm_text: str) -> Optional[float]:
    if not cm_text:
        return None
    # Extract first number
    m = re.search(r"([0-9]+(?:[\.,][0-9]+)?)", cm_text)
    if not m:
        return None
    num = m.group(1).replace(",", ".")
    try:
        cm_val = float(num)
        return round(cm_val / 100.0, 3)
    except ValueError:
        return None


def _build_dimensions(width_cm: str, depth_cm: str, height_cm: str) -> List[Dict[str, Any]]:
    dims: List[Dict[str, Any]] = []
    width_m = _cm_to_m_value(width_cm) if width_cm else None
    length_m = _cm_to_m_value(depth_cm) if depth_cm else None
    # Rugs typically use Width and Length
    if width_m is not None:
        dims.append({"@type": "QuantitativeValue", "value": width_m, "unitCode": "MTR", "name": "Width"})
    if length_m is not None:
        dims.append({"@type": "QuantitativeValue", "value": length_m, "unitCode": "MTR", "name": "Length"})
    return dims


def _derive_identifier(url: str) -> str:
    slug = url.strip().rstrip("/").split("/")[-1]
    return slug


def _detect_pattern(title: str, description: str) -> Optional[str]:
    text = f"{title} {description}".lower()
    if re.search(r"geo(metri|métr)\w*", text):
        return "Geometric"
    if re.search(r"ray[ée]s?|striped", text):
        return "Striped"
    if re.search(r"uni\b|solid", text):
        return "Solid"
    return None


def build_schema(url: str, html: str, meta_tags: List[Dict[str, str]]) -> Dict[str, Any]:
    name = find_title(html, meta_tags) or ""
    description = find_description(html, meta_tags) or ""
    images = find_images(meta_tags)
    price, currency = find_price_and_currency(meta_tags, html)
    sku = find_sku(html) or ""

    # Attempt to find attributes (en + fr labels)
    material_text = find_attribute_value(html, ["Material", "Matière", "Matériau", "Materiau"]) or ""
    material_list = _parse_material_list(material_text) if material_text else []
    weight = find_attribute_value(html, ["Weight", "Poids"]) or "Varies by size"
    width_cm = find_attribute_value(html, ["Width", "Largeur"]) or ""
    depth_cm = find_attribute_value(html, ["Depth", "Profondeur"]) or ""
    height_cm = find_attribute_value(html, ["Height", "Hauteur"]) or ""
    dimensions = _build_dimensions(width_cm, depth_cm, height_cm)

    color = find_attribute_value(html, ["Color", "Couleur"]) or ""
    pattern = _detect_pattern(name, description) or ""

    identifier = _derive_identifier(url)

    schema: Dict[str, Any] = {
        "@context": "https://schema.org",
        "@type": "Product",
        "name": name,
        "image": images,
        "description": description,
        "brand": {"@type": "Brand", "name": "Softicated"},
        "sku": sku,
        "offers": {
            "@type": "Offer",
            "url": url,
            "priceCurrency": currency or "",
            "price": price or "",
            "priceValidUntil": "2025-12-31",
            "itemCondition": "https://schema.org/NewCondition",
            "availability": "https://schema.org/InStock",
            "seller": {"@type": "Organization", "name": "Softicated"},
        },
        "material": material_list if material_list else ( [material_text] if material_text else [] ),
        "weight": weight,
        "dimensions": dimensions,
        "color": color,
        "pattern": pattern,
        "additionalType": "https://schema.org/CreativeWork",
        "identifier": identifier,
    }
    return schema


def compact_json(obj: Dict[str, Any]) -> str:
    return json.dumps(obj, separators=(",", ":"), ensure_ascii=False)


def write_csv(rows: List[Dict[str, str]], out_path: str) -> None:
    with open(out_path, "w", newline="", encoding="utf-8") as f:
        writer = csv.writer(f)
        writer.writerow(["page url", "schema code"])
        for row in rows:
            writer.writerow([row["url"], row["schema"]])


def process_url(url: str) -> Dict[str, str]:
    try:
        html = fetch_html(url)
        meta_tags = parse_meta_tags(html)
        schema = build_schema(url, html, meta_tags)
        return {"url": url, "schema": compact_json(schema)}
    except (HTTPError, URLError) as e:
        return {"url": url, "schema": compact_json({"error": f"network: {e}"})}
    except Exception as e:
        return {"url": url, "schema": compact_json({"error": str(e)})}


def main(urls: List[str]) -> int:
    rows = [process_url(u) for u in urls]
    out_path = "/workspace/product_schema.csv"
    write_csv(rows, out_path)
    print(out_path)
    return 0


if __name__ == "__main__":
    input_urls = [
        "https://softicated.com/produit/tapis-design-sur-mesure-greys-and-white-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-ocean-softicated-2/",
        "https://softicated.com/produit/tapis-gravity-sur-mesure-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-milky-way-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-natural-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-scenery-softicated-2/",
        "https://softicated.com/produit/tapis-design-sur-mesure-beach-softicated/",
        "https://softicated.com/produit/tapis-rectangulaire-uni-beige/",
        "https://softicated.com/produit/rectangular-yellow/",
        "https://softicated.com/produit/econyl-materiau-tapis-rug-soft-re-creation/",
        "https://softicated.com/produit/tapis-design-sur-mesure-blues-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-ocean-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-coast-softicated/",
        "https://softicated.com/produit/tapis-rugs-softicated-structure-en-nid-dabeilles-orange/",
        "https://softicated.com/produit/tapis-design-sur-mesure-flowery-softicated/",
        "https://softicated.com/produit/tapis-rectangulaire-sur-mesure-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-lush-softicated/",
        "https://softicated.com/produit/tapis-design-sur-mesure-colored-softicated/",
    ]
    sys.exit(main(input_urls))