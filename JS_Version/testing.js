const { Configuration, OpenAIApi } = require("openai");
require('dotenv').config();

const configuration = new Configuration({
    apiKey: process.env.OPENAI_API_KEY,
});
const openai = new OpenAIApi(configuration);

// Prompt variables
console.log('Generating prompt...')
const question = "What layer of the OSI model connecting a cat6 cable to a laptop?";
const answer = "physical";
const prompt = "Question: " + question + " Answer: " + answer;

//Prompt function
async function gradeAnswer() {
    let repeat = true;
    let attempts = 0;
    let maxAttempts = 2;

    // If it returns an invalid response, try again
    while(repeat && attempts < maxAttempts) {

        // Set the tokens based on query length
        var tokens = 0;
        tokens = Math.round(prompt.length * 0.05);
        if (tokens < 10) {
            tokens = 10;
        }
        console.log('Prompt length: ' + prompt.length)
        console.log('Tokens in call: ' + tokens)

        // API Call
        console.log('Awaiting response...')
        repeat = false;
        attempts++;
        try {
            const completion = await openai.createChatCompletion({
                model: "gpt-3.5-turbo",
                messages: [
                    {"role": "system", "content": "You're a professor marking the student's answer 'Answer:' to the quiz question 'Question:'. You can only answer with: 'Pass' or 'Fail' and ensure high accuracy."},
                    {"role": "user", "content": prompt},
                ],
                max_tokens: tokens,
            });
            response = (completion.data.choices[0].message.content);
            if (response.includes("Pass")) {
                repeat = false;
                console.log("Pass");
            }
            else if (response.includes("Fail")) {
                repeat = false;
                console.log("Fail");
            }
            // If the result is invalid, run it again
            else {
                repeat = true;
                console.log("Error, running again.");
                console.log("Response contained: " + response);
            }
        } catch (error) {
            repeat = true;
            console.log(error);
        }
    }
    // Max of 2 retries
    if (repeat && attempts >= maxAttempts) {
        console.log("Maximum retry attempts reached.");
    }
}
gradeAnswer();
