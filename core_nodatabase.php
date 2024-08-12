<?php

// Hardcoded for testing
$question = "What is the distance between the earth and the sun?";
$answer = "151 million km";

// Prompt that morphs by $type
// 0 = Translation, 1 = Plagiarism, 2 = Assessment
class Prompt {
    public $question;
    public $conditions;
    public $type;
    public $prompt;

    public function __construct($answer, $conditions, $type) {

        // Translation
        if ($type == 0) {
            $tokens = str_word_count($answer) * 3;
            $this->prompt = array(
                "model" => "gpt-3.5-turbo",
                "messages" => array(
                    array(
                        "role" => "system", 
                        "content" => "Translate this from " . $conditions . " to English" 
                    ),
                    array(
                        "role" => "user", 
                        "content" => $answer
                    )
                ),
                "max_tokens" => $tokens,
                "temperature" => 0.0
            );
        }

        // Plagiarism
        else if ($type == 1) {
            $this->prompt = array(
                "model" => "gpt-3.5-turbo",
                "messages" => array(
                    array(
                        "role" => "system", 
                        "content" => "Determine whether the user's Answer to the Question is similar to a chatgpt response. You can only answer with: 'Yes' or 'No'."
                    ),
                    array(
                        "role" => "assistant", 
                        "content" => "Question: " . $conditions
                    ),
                    array(
                        "role"  => "user",
                        "content" => "Answer: " . $answer
                    )
                ),
                "max_tokens" => 2,
                "temperature" => 0.0,
            );
        }

        // Assess
        else if ($type == 2) {
            $promptString = "Question:\n" . $conditions . "\nAnswer:\n" . $answer;
            $this->prompt = array(
                "model" => "gpt-3.5-turbo",
                "messages" => array(
                    array(
                        "role" => "system", 
                        "content" => "You're marking the high school student's answer 'Answer:' to the quiz question 'Question:'. You can only respond with: 'Pass' or 'Fail' and if their 'Answer: ' is incomplete, respond with 'Fail'."
                    ),
                    array(
                        "role" => "user", 
                        "content" => $promptString
                    )
                ),
                "max_tokens" => 5,
                "temperature" => 0.0
            );
        }
    }

    // Sending the cURL request
    function sendRequest($data) {
        $apiKey = getenv("OPENAI_API_KEY");
        global $DB;
        $url = "https://api.openai.com/v1/chat/completions";
        $jsonData = json_encode($data);
        $curl = curl_init($url);
        curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, true);
        curl_setopt($curl, CURLOPT_CAINFO, './cacert.pem');
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_HTTPHEADER, array(
            "Content-Type: application/json",
            "Authorization: Bearer " . $apiKey
            )
        );
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
        $response = curl_exec($curl);
        if ($response === false) {
            $error = curl_error($curl);
            echo "Curl error: " . $error;
        } 
        else {
            $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
            // Get the content from our response
            if ($statusCode >= 200 && $statusCode < 300) {
                $responseArray = json_decode($response, true);
                $content = $responseArray['choices'][0]['message']['content'];
                return $content;
            }
            // Add this to a patch queue and retry later
            else if ($statusCode == 429) {
                echo "Too many requests made to the API.";
                return;
            }
            else {
                echo "Request failed with status code: " . $statusCode;
                return;
            }
        }
        curl_close($curl);
    }
}

// Build the final query
function buildQuery($question, $answer) {

    // Does this need to be translated
    $translation = false;
    $language = "Maori";

    // Are we checking for plagiarism
    $plagiarism = true;

    // Are we assessing this
    $assess = true;


    // Translation
    if ($translation == true) {
        $Request = new Prompt($answer, $language, 0);
        echo "Answer given is in Maori, translating answer... \n" . $answer;
        $response = $Request->sendRequest($Request->prompt);
        echo "Translation response: \n" . $response;
        $answer = $response;
    }

    // Plagiarism check
    if ($plagiarism == true) {
        $sendit = true;
        $retries = 0;
        $Request = new Prompt($answer, $question, 1);
        while ($sendit == true && $retries < 3) {
            $response = $Request->sendRequest($Request->prompt);
            if ($response == "Yes" || $response == "Yes.") {
                echo "Failed plagiarism check. Retrying...";
                $retries++;
            }
            else if ($response == "No" || $response == "No.") {
                echo "Passed plagiarism check.";
                $sendit = false;
            }
            else {
                echo "Invalid response. Retrying...";
                $retries++;
            }
        }
    }

    // Mark the assessment
    if ($assess == true) {
        $sendit = true;
        $retries = 0;
        $Request = new Prompt($answer, $question, 2);
        while ($sendit == true && $retries < 3) {
            $response = $Request->sendRequest($Request->prompt);
            $pass = strpos($response, "Pass");
            $fail = strpos($response, "Fail");
            if ($pass !== false) {
                echo 'Pass!';
                $sendit = false;
            }
            else if ($fail !== false) {
                echo 'Fail.';
                $sendit = false;
            }
            else {
                echo "Invalid response. Retrying...\n";
                $retries++;
            }
        }  
    }
}

// Run the request/s
buildQuery($question, $answer);
?>