import csv
import json
import os
import re
from typing import List, Tuple


CSV_PATH = "/workspace/icart_dynamic_keywords - icart_dynamic_keywords (1).csv"
OUTPUT_PATH = "/workspace/example.json"


BENEFITS = [
    "Upselling & Cross-selling",
    "Product Bundles & Volume Discounts",
    "Progress Bars & Free Gifts",
    "Sticky/Slide Cart Drawer & Cart Popups",
    "In-cart Offers to Boost AOV",
]


def count_words(text: str) -> int:
    tokens = re.findall(r"\b[\w'-]+\b", text)
    return len(tokens)


def clean_spaces(text: str) -> str:
    return re.sub(r"\s+", " ", text).strip()


def correct_spelling_minimal(keyword: str) -> str:
    # Minimal, safe corrections for common typos present in e-commerce terms.
    corrections = {
        "shopify": "Shopify",
        "aov": "AOV",
        "upsell": "upsell",
        "upselling": "upselling",
        "cross sell": "cross-sell",
        "cross selling": "cross-selling",
        "multicurrency": "multi-currency",
        "giftwrap": "gift wrap",
    }
    text = keyword
    for wrong, right in corrections.items():
        pattern = re.compile(rf"\b{re.escape(wrong)}\b", re.IGNORECASE)
        text = pattern.sub(right, text)
    return clean_spaces(text)


def make_title(keyword: str) -> str:
    keyword_clean = clean_spaces(keyword)
    num_words = count_words(keyword_clean)
    if num_words >= 8:
        # Must return exactly the keyword for 8+ words (no corrections)
        return keyword_clean

    # Create an 8–12 word H1-style title preserving core meaning
    base = correct_spelling_minimal(keyword_clean)

    # Heuristic expansions to reach 8–12 words without changing meaning
    # Avoid hype or restricted words
    prefixes = [
        "The best way to",
        "Simple ways to",
        "How Shopify merchants can",
        "Practical tips to",
    ]

    for prefix in prefixes:
        candidate = f"{prefix} {base} with iCart"
        wc = count_words(candidate)
        if 8 <= wc <= 12:
            return candidate

    # Fallback: append neutral context until we reach 8 words
    extras = ["on Shopify", "using iCart", "today"]
    candidate = base
    for extra in extras:
        if count_words(candidate) < 8:
            candidate = f"{candidate} {extra}"
    return candidate


def choose_benefits(keyword: str) -> List[str]:
    text = keyword.lower()
    picks: List[str] = []

    def add(item: str):
        if item not in picks:
            picks.append(item)

    # Keyword-driven selection
    if any(word in text for word in ["bundle", "bundling", "volume"]):
        add("Product Bundles & Volume Discounts")
    if any(word in text for word in ["progress", "gift", "free gift", "goal"]):
        add("Progress Bars & Free Gifts")
    if any(word in text for word in ["drawer", "popup", "popups", "sticky", "slide cart"]):
        add("Sticky/Slide Cart Drawer & Cart Popups")
    if any(word in text for word in ["upsell", "cross", "cross-sell", "cross sell"]):
        add("Upselling & Cross-selling")
    if any(word in text for word in ["aov", "average order value", "increase aov", "boost sales"]):
        add("In-cart Offers to Boost AOV")

    # Ensure exactly 3 items to better meet the 25–30 word limit
    for b in BENEFITS:
        if len(picks) >= 3:
            break
        if b not in picks:
            add(b)

    # Guarantee exactly 3 (never more than 4 allowed, but we keep 3 for brevity)
    if len(picks) < 3:
        for b in BENEFITS:
            if b not in picks:
                add(b)
            if len(picks) >= 3:
                break

    if len(picks) > 3:
        picks = picks[:3]

    return picks


def make_description(keyword: str) -> str:
    benefits = choose_benefits(keyword)
    kw = clean_spaces(keyword)

    # Compact two-sentence pattern to meet 25–30 words reliably with 3 benefits
    first = f"iCart helps Shopify stores grow AOV for '{kw}' with {benefits[0]}, {benefits[1]}, and {benefits[2]}."
    second_options = [
        "Shoppers add more with timely prompts.",
        "Shoppers add more with timely prompts and clear value.",
        "Shoppers add more thanks to timely prompts and clear value.",
    ]
    # Start with the shortest second sentence, then expand if needed
    sentence = f"{first} {second_options[0]}"

    def wc(s: str) -> int:
        return count_words(s)

    # If under 25 words, grow the second sentence
    if wc(sentence) < 25:
        sentence = f"{first} {second_options[1]}"
    if wc(sentence) < 25:
        sentence = f"{first} {second_options[2]}"

    # If above 30, shrink second sentence
    if wc(sentence) > 30:
        sentence = f"{first} Shoppers add more."

    # Final enforcement: if still outside, tighten by removing "for" phrase
    if wc(sentence) > 30:
        first_alt = f"iCart helps Shopify stores grow AOV with {benefits[0]}, {benefits[1]}, and {benefits[2]}."
        sentence = f"{first_alt} Shoppers add more."
    if wc(sentence) < 25:
        sentence += " Timely prompts appear when interest is high."

    sentence = clean_spaces(sentence)
    if not sentence.endswith('.'):
        sentence += '.'
    return sentence


def read_keywords(path: str) -> List[str]:
    keywords: List[str] = []
    with open(path, newline="", encoding="utf-8") as f:
        reader = csv.DictReader(f)
        field = None
        # Find a column that looks like the keyword field
        headers = [h.strip() for h in reader.fieldnames or []]
        for name in headers:
            if name.lower() in {"keyword", "keywords", "keyphrase", "query"}:
                field = name
                break
        if field is None and headers:
            # Fallback to first column
            field = headers[0]

        for row in reader:
            value = (row.get(field, "") or "").strip()
            if value:
                keywords.append(value)
    return keywords


def generate_records(keywords: List[str]) -> List[dict]:
    records: List[dict] = []
    for kw in keywords:
        title = make_title(kw)
        description = make_description(kw)
        records.append({
            "title": title,
            "short_description": description,
        })
    return records


def main() -> None:
    if not os.path.exists(CSV_PATH):
        raise FileNotFoundError(f"CSV not found at {CSV_PATH}")
    keywords = read_keywords(CSV_PATH)
    records = generate_records(keywords)

    with open(OUTPUT_PATH, "w", encoding="utf-8") as f:
        json.dump(records, f, ensure_ascii=False, indent=2)

    print(f"Wrote {len(records)} records to {OUTPUT_PATH}")


if __name__ == "__main__":
    main()

