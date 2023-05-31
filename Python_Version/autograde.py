import os
import openai
openai.api_key = os.getenv("OPENAI_API_KEY")

# Raw Data/Inout
question = "What is the distance between the earth and sun roughly?"
answer = "151 million km"

# Prompt
prompt = "Question:/n" + question + "/nAnswer:/n" + answer

# Call the API
attempts = 0
run = True
while run and attempts < 3:
  attempts += 1
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
  response = completion.choices[0].message.content
  if "Pass" in response:
      run = False
      print('Pass')
  elif "Fail" in response:
      run = False
      print('Fail')
  elif attempts == 2:
      print('Max attempts reached')
  else:
      print('Error')
