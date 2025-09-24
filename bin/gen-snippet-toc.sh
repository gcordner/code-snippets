#!/usr/bin/env bash
# Generate a Markdown table of snippet files with links and the "Purpose" paragraph.
# - Scans *.php and *.sql
# - Extracts ONLY the "Purpose:" block from the first comment, stops at blank line or new section
# - Defaults output to CONTENTS.md (so README.md stays intact)
#
# Usage:
#   bin/gen-snippet-toc.sh                      # writes CONTENTS.md with relative links
#   ABSOLUTE_LINKS=1 bin/gen-snippet-toc.sh     # writes CONTENTS.md with GitHub blob links
#   ABSOLUTE_LINKS=1 bin/gen-snippet-toc.sh OUTPUT.md
#
# Requirements: bash, git, awk, sed

set -euo pipefail

OUTPUT_FILE="${1:-CONTENTS.md}"
ABSOLUTE="${ABSOLUTE_LINKS:-0}"

BRANCH="$(git rev-parse --abbrev-ref HEAD 2>/dev/null || echo "main")"
REPO_URL=""
if [[ "$ABSOLUTE" == "1" ]]; then
  origin="$(git config --get remote.origin.url || true)"
  # Normalize git@ and https remotes to https://github.com/owner/repo
  if [[ "$origin" =~ ^git@github\.com:(.*)\.git$ ]]; then
    REPO_URL="https://github.com/${BASH_REMATCH[1]}"
  elif [[ "$origin" =~ ^https://github\.com/(.*)\.git$ ]]; then
    REPO_URL="https://github.com/${BASH_REMATCH[1]}"
  elif [[ "$origin" =~ ^https://github\.com/(.*)$ ]]; then
    REPO_URL="https://github.com/${BASH_REMATCH[1]}"
  fi
fi

# Header
{
  echo "| File | Purpose |"
  echo "|------|---------|"
} > "$OUTPUT_FILE"

# BSD/POSIX-awk extractor: get ONLY the Purpose paragraph from the first block comment,
# stopping at blank line or at a recognized new section header.
extract_purpose() {
  awk '
    BEGIN {
      inblock=0; seenblock=0; capturing=0
      buf=""
    }
    # Start of first /* ... */ block
    /\/\*/ && seenblock==0 { inblock=1; seenblock=1 }
    {
      line=$0
      if (inblock==1) {
        # strip leading "* " padding
        sub(/^[[:space:]]*\*+[[:space:]]?/, "", line)
        low=line
        # lowercase copy for case-insensitive tests
        gsub(/[A-Z]/, "", low)  # cheap lower via removing caps? nope; better manual:
      }
    }
    # Implement lowercase safely (BSD awk lacks tolower() in some versions)
    # We emulate a simple lowercase by mapping A-Z -> a-z:
    function tolower_str(s,   i,c,o) {
      o=""
      for (i=1; i<=length(s); i++) {
        c=substr(s,i,1)
        if (c>="A" && c<="Z") c=sprintf("%c", ord(c)+32)
        o=o c
      }
      return o
    }
    # ord helper
    function ord(c) { return index("abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ", c)>26 ? 64+index("ABCDEFGHIJKLMNOPQRSTUVWXYZ", c)-26 : 96+index("abcdefghijklmnopqrstuvwxyz", c) } # fallback; not perfect but unused for A-Z only

    {
      if (inblock==1) {
        low=tolower_str(line)

        # Start capturing on "purpose:"
        if (capturing==0 && match(low, /^purpose:[[:space:]]*/)) {
          capturing=1
          # grab inline text after "Purpose:"
          sub(/^[Pp]urpose:[[:space:]]*/, "", line)
          buf=line
          next
        }

        if (capturing==1) {
          # Stop on blank line
          if (line ~ /^[[:space:]]*$/) { capturing=2 }
          # Stop on a new section header (keep this list short & safe)
          else if (match(low, /^(what it does:|why needed:|client benefit:|hook timing:|usage:|adjustments:|output:|notes:|use case:|problem:|technical details:|expected results:)/)) {
            capturing=2
          } else {
            # continue paragraph
            if (buf=="") buf=line; else buf=buf "\n" line
          }
        }
      }
    }

    # End of first block
    /\*\// && inblock==1 { inblock=0 }

    END {
      gsub(/\r/, "", buf)
      print buf
    }
  ' "$1"
}

# Walk files (skip vendor/node_modules/.git)
while IFS= read -r -d '' file; do
  rel="${file#./}"

  purpose="$(extract_purpose "$file")"
  if [[ -z "$purpose" ]]; then
    purpose="—"
  fi

  # Escape pipes and fold newlines for Markdown
  purpose_escaped="$(printf "%s" "$purpose" | sed 's/|/\\|/g' | sed ':a;N;$!ba;s/\n/<br>/g')"

  if [[ -n "$REPO_URL" ]]; then
    href="${REPO_URL}/blob/${BRANCH}/${rel}"
  else
    href="${rel}"
  fi

  printf '| [%s](%s) | %s |\n' "$rel" "$href" "$purpose_escaped" >> "$OUTPUT_FILE"
done < <(find . -type d \( -name .git -o -name vendor -o -name node_modules \) -prune -o \
               -type f \( -name "*.php" -o -name "*.sql" \) -print0 | sort -z)

echo "✅ Wrote $OUTPUT_FILE"
