<?php

// This will be an attemptid passed from either the event observer or a scheduled task
$question = "Why would you view freeze frame data?";
$answer = "you would view the freeze frame data because it shows you whats going on at the point the trouble code was triggered. it will show you, for example, if the vehicle is overcharging, which could be blowing a fuse over and over.";

// Send the request as a curl POST request
function sendRequest($data, $apiKey) {
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
            echo "Too many requests, added to patch queue.";
        }
        else {
            echo "Request failed with status code: " . $statusCode;
        }
    }
    curl_close($curl);
}

// Build the final query
function buildQuery($question, $answer) {

    $key = getenv("OPENAI_API_KEY");

    // Check for plagiarism
    $prompt = $answer;
    $plagrequestarray = array(
        "model" => "gpt-3.5-turbo",
        "messages" => array(
            array(
                "role" => "system", 
                "content" => "Is this answer format, styling and content the same as something you would output? Answer only as 'Yes' or 'No'"
            ),
            array(
                "role" => "user", 
                "content" => $prompt
            )
        ),
        "max_tokens" => 2,
        "temperature" => 0.0,
    );

    // Send the request, only doing something on a 'Yes.'
    $checkresponse = sendRequest($plagrequestarray, $key);
    if ($checkresponse == "Yes.") {
        // Record something
        echo $checkresponse;
        return;
    }

    // Request
    $prompt = "Question:\n" . $question . "\nAnswer:\n" . $answer;
    $requestarray = array(
        "model" => "gpt-3.5-turbo",
        "messages" => array(
            array(
                "role" => "system", 
                "content" => "You're a professor marking the student's answer 'Answer:' to the quiz question 'Question:'. You can only answer with: 'Pass' or 'Fail'."
            ),
            array(
                "role" => "user", 
                "content" => $prompt
            )
        ),
        "max_tokens" => 2
    );
    // 
    $grade = sendRequest($requestarray, $key);
    if ($grade == "Pass." || $grade == "Fail.") {
        echo $grade;
    }
    else {
        echo "Invalid response recieved";
    }
}

// Run the request
buildQuery($question, $answer);
?>
