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


def build_schema(url: str, html: str, meta_tags: List[Dict[str, str]]) -> Dict[str, Any]:
    name = find_title(html, meta_tags) or ""
    description = find_description(html, meta_tags) or ""
    images = find_images(meta_tags)
    price, currency = find_price_and_currency(meta_tags, html)
    sku = find_sku(html) or ""

    # Attempt to find some attributes (en + fr labels)
    color = find_attribute_value(html, ["Color", "Couleur"]) or ""
    material = find_attribute_value(html, ["Material", "Matière", "Matériau", "Materiau"]) or ""
    weight = find_attribute_value(html, ["Weight", "Poids"]) or ""
    width_cm = find_attribute_value(html, ["Width", "Largeur"]) or ""
    depth_cm = find_attribute_value(html, ["Depth", "Profondeur"]) or ""
    height_cm = find_attribute_value(html, ["Height", "Hauteur"]) or ""
    usage = find_attribute_value(html, ["Usage", "Utilisation"]) or ""
    origin = find_attribute_value(html, ["Origin", "Origine"]) or ""
    collection = find_attribute_value(html, ["Collection"]) or "Signet Ring"

    schema: Dict[str, Any] = {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": name,
        "image": images,
        "description": description,
        "sku": sku,
        "brand": {"@type": "Brand", "name": "Softicated"},
        "offers": {
            "@type": "Offer",
            "url": url,
            "priceCurrency": currency or "",
            "price": price or "",
            "availability": "https://schema.org/InStock",
            "itemCondition": "https://schema.org/NewCondition",
            "seller": {"@type": "Organization", "name": "Softicated"},
        },
        "color": color,
        "material": material,
        "weight": weight,
        "additionalProperty": [
            {"@type": "PropertyValue", "name": "Width", "value": (width_cm + " cm") if width_cm else ""},
            {"@type": "PropertyValue", "name": "Depth", "value": (depth_cm + " cm") if depth_cm else ""},
            {"@type": "PropertyValue", "name": "Height", "value": (height_cm + " cm") if height_cm else ""},
            {"@type": "PropertyValue", "name": "Usage", "value": usage},
            {"@type": "PropertyValue", "name": "Origin", "value": origin},
            {"@type": "PropertyValue", "name": "Collection", "value": collection},
        ],
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