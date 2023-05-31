import os
import openai
openai.api_key = os.getenv("OPENAI_API_KEY")

# Prompt
languagein = input('What language are you translating from? ')
languageout = input('What language are you translating to? ')
prompt = input('Enter your text to translate: ')

# Get max tokens. Number of words multiplied by 3. 
# Tokens are expected to be roughly .75 per word and we're accomodating here for large translation outputs.
tokens = round(len(prompt.split()) * 3)
if tokens < 5:
  tokens = 5
print('-- Max Tokens: ' + str(tokens) + " --")

# Call the API
completion = openai.ChatCompletion.create(
  model = "gpt-3.5-turbo",
  messages = [
    {"role": "system", "content": "Translate " + languagein + " to " + languageout},
    {"role": "user", "content": prompt}
  ],
  max_tokens = tokens,
  temperature = 0
)
print(completion.choices[0].message.content)
