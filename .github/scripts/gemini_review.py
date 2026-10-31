import os
import requests
from google import genai

github_token = os.environ["GITHUB_TOKEN"]
gemini_key = os.environ["GEMINI_API_KEY"]
repo = os.environ["GITHUB_REPOSITORY"]
pr_number = os.environ["PR_NUMBER"]

headers = {
    "Authorization": f"Bearer {github_token}",
    "Accept": "application/vnd.github.v3.diff",
}

# 1. Fetch the pull request diff
diff_url = f"https://api.github.com/repos/{repo}/pulls/{pr_number}"
diff_res = requests.get(diff_url, headers=headers)
diff_text = diff_res.text

if not diff_text.strip():
    print("Diff is empty, skipping review.")
    exit(0)

# Truncate if diff exceeds API prompt limits
truncated_diff = diff_text[:30000]

# 2. Call Gemini
client = genai.Client(api_key=gemini_key)
prompt = f"""You are a senior code reviewer. Review the following Git diff for bugs, security vulnerabilities, edge cases, and performance improvements.
Provide actionable suggestions using markdown formatting.

Diff:
```diff
{truncated_diff}
