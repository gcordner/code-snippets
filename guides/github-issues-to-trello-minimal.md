# GitHub Issues → Trello Automation (Minimal Setup)

This guide shows how to automatically create a Trello card whenever a new GitHub issue is opened.  
It uses GitHub Actions and Trello’s API.  
**This version does *not* post a comment back on the GitHub issue.**

---

## 1. Prerequisites

- A GitHub repository where you can add workflows and secrets.
- A Trello account with access to the board/list where cards should be created.

---

## 2. Get Trello credentials

1. **API Key & Token**
   - Go to [https://trello.com/power-ups/admin/](https://trello.com/power-ups/admin/).
   - Create a new *Power-Up* (just a stub, no code needed).
   - Inside the Power-Up’s settings, copy the **API Key**.
   - Use this API Key to generate a Token:  
     [https://trello.com/1/authorize?expiration=never&name=GitHubIntegration&scope=read,write&response_type=token&key=YOURKEYHERE](https://trello.com/1/authorize?expiration=never&name=GitHubIntegration&scope=read,write&response_type=token&key=YOURKEYHERE)  
     Replace `YOURKEYHERE` with your API key. Approve access, then copy the token.

   - Result:  
     - `TRELLO_KEY` = your API Key  
     - `TRELLO_TOKEN` = your Token  

2. **List ID**
   - Open your Trello board in a browser.  
   - Add `.json` to the board’s URL (e.g. `https://trello.com/b/abcd1234/my-board.json`).  
   - Search the JSON for the list name (like `"To Do"`).  
   - Copy its `"id"` value.  
   - Result:  
     - `TRELLO_LIST_ID` = the ID string for your Trello list

---

## 3. Add secrets to GitHub

1. Go to your repo → **Settings → Secrets and variables → Actions**.
2. Add three new repository secrets:
   - `TRELLO_KEY`
   - `TRELLO_TOKEN`
   - `TRELLO_LIST_ID`

---

## 4. Create the GitHub Actions workflow

In your repo, create a new file:

```
.github/workflows/issue-to-trello.yml
```

Contents:

```yaml
name: Create Trello card for new issues

on:
  issues:
    types: [opened]

permissions:
  contents: read

jobs:
  create_trello_card:
    runs-on: ubuntu-latest
    steps:
      - name: Install jq
        run: sudo apt-get update && sudo apt-get install -y jq

      - name: Create Trello card
        run: |
          ISSUE_TITLE="${{ github.event.issue.title }}"
          ISSUE_URL="${{ github.event.issue.html_url }}"
          ISSUE_BODY="${{ github.event.issue.body || '' }}"
          DESC="GitHub Issue: ${ISSUE_URL}

${ISSUE_BODY}"

          RESP=$(curl -sS -X POST "https://api.trello.com/1/cards"             --data-urlencode "idList=${{ secrets.TRELLO_LIST_ID }}"             --data-urlencode "name=${ISSUE_TITLE}"             --data-urlencode "desc=${DESC}"             --data-urlencode "key=${{ secrets.TRELLO_KEY }}"             --data-urlencode "token=${{ secrets.TRELLO_TOKEN }}")

          echo "$RESP" > resp.json
          CARD_ID=$(jq -r '.id // empty' resp.json)
          CARD_URL=$(jq -r '.shortUrl // empty' resp.json)

          if [ -z "$CARD_ID" ] || [ -z "$CARD_URL" ]; then
            echo "Failed to create Trello card. Response below:"
            cat resp.json
            exit 1
          fi

          echo "::notice title=Created Trello card::$CARD_URL"
```

---

## 5. Test

1. Commit and push the workflow to your repo’s default branch.
2. Open a new GitHub issue.
3. Check:
   - The **Actions tab** → confirm workflow ran successfully.
   - Your Trello board → the new card should appear.
   - Workflow logs will show a `Created Trello card` notice with the card URL.

---

## 6. Troubleshooting

- **401 Unauthorized** → Check that `TRELLO_KEY` and `TRELLO_TOKEN` are correct.
- **Card not created** → Verify the `TRELLO_LIST_ID` points to an active list.
- **Workflow doesn’t trigger** → Make sure the YAML file is in `.github/workflows/` on the default branch.

---

✅ Done! Now every new GitHub issue automatically creates a Trello card in your chosen list.
