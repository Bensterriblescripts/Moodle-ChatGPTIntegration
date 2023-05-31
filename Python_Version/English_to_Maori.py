import os
import openai
openai.api_key = os.getenv("OPENAI_API_KEY")

# Prompt
prompt = "Hello, my name is Ben. How are you all today?"

# Get max tokens. Estimate given was .75 so rounding to 8 for accuracy
tokens = round(len(prompt) * .8)
if tokens < 10:
  tokens = 10

# Call the API
completion = openai.ChatCompletion.create(
  model = "gpt-3.5-turbo",
  messages = [
    {"role": "system", "content": "Translate English to Maori using the Ngapuhi dialect"},
    {"role": "user", "content": prompt}
  ],
  max_tokens = tokens,
  temperature = 0
)
print(completion.choices[0].message.content)
