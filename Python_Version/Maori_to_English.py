import os
import openai
openai.api_key = os.getenv("OPENAI_API_KEY")

# Prompt
prompt = "Kia ora ko sou yi toku ingoa"

# Get max tokens. Estimate given was .75 so rounding to 8 for accuracy
tokens = round(len(prompt) * .8)
if tokens < 10:
  tokens = 10

# Call the API
completion = openai.ChatCompletion.create(
  model = "gpt-3.5-turbo",
  messages = [
    {"role": "system", "content": "Translate this Maori into English"},
    {"role": "user", "content": prompt}
  ],
  max_tokens = tokens,
  temperature = 0
)
print(completion.choices[0].message.content)
