import os
import openai
openai.api_key = os.getenv("OPENAI_API_KEY")
translation = False

# Raw Data/Inout
question = "What is the distance between the earth and sun roughly?"
answer = "151 miriona km"
translation = True
language = "Maori"

# Do we need to translate this?
if translation == True:

  rawanswer = answer
  print('Translating answer from ' + language)

  # Get max tokens. Estimate given was .75 so rounding to 8 for accuracy
  tokens = round(len(rawanswer) * 3)
  if tokens < 10:
    tokens = 10

  # Call the API
  completion = openai.ChatCompletion.create(
    model = "gpt-3.5-turbo",
    messages = [
      {"role": "system", "content": "Translate " + language + " to English"},
      {"role": "user", "content": rawanswer}
    ],
    max_tokens = tokens,
    temperature = 0
  )
  answer = completion.choices[0].message.content
  print('Translated into: ' + answer)

# Prompt
prompt = "Question:/n" + question + "/nAnswer:/n" + answer

# Call the API
print('Sending Request....')
completion = openai.ChatCompletion.create(
  model = "gpt-3.5-turbo",
  messages = [
    {"role": "system", "content": "You're a professor marking the student's answer 'Answer:' to the quiz question 'Question:'. You can only answer with: 'Pass' or 'Fail'."},
    {"role": "user", "content": prompt}
  ],
  max_tokens = 2,
  temperature = 0
)
print(completion.choices[0].message.content)