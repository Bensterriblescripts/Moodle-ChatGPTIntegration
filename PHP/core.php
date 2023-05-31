<?php

// This will be an attemptid passed from either the event observer or a scheduled task
$attemptid = 192768;

// Send the request as a curl POST request
function sendRequest($data, $apiKey, $qattemptid) {
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
            //Build the array
            $queueMap = array(
                "qattemptid"    => $qattemptid,
                "data"          => $data,
                "timeadded"     => time(),
                "status"        => "pending"
            );
            $DB->insert_record("openai_queue", $queueMap);
        }
        else {
            echo "Request failed with status code: " . $statusCode;
        }
    }
    curl_close($curl);
}

// Update the grade in the database
function updateGrade($grade, stdClass $questionobject) {
    global $DB;

    if ($grade == "Pass.") {
        $grade = 1;
    }

    // Failed

    // Error, return
    else {
        $queueMap = array(
            "qattemptid"    => $qattemptid,
            "data"          => $data,
            "timeadded"     => time(),
            "status"        => "pending"
        );
        return;
    }
}

// Build the API request and translate if required
function buildQuery($attemptid) {

    global $DB;
    $key = getenv("OPENAI_API_KEY");

    echo "Building Request.....\n";
    
    // Grab a each question attempt by quiz
    if (!$attempt = $DB->get_record('quiz_attempts', ['id' => $attemptid])) {
        return;
    }
    // Conditions
    if ($attempt->preview == 0) {
        return;
    }
    if ($attempt->state == 'inprogress') {
        return;
    }

    // Now grab all the question attempts
    if (!$questionattempts = $DB->get_records('question_attempts', ['questionusageid' => $attempt->uniqueid])) {
        return;
    }

    // Loop through the whole attempt
    foreach ($questionattempts as $questionobject) {

        // Store the question in a string
        $question = $questionobject->questionsummary;
        // Store the answer in a string
        $answer = $questionobject->responsesummary;

        // Translation
        $translate = false;
        $language = "Maori";
        if ($translate === true) {

            // Tokens are roughly .75 per word, however we ensure extra tokens for longer translations.
            // Here we're multiplying the input by 3 to give 3.75x the length of the input as leeway.
            $tokens = str_word_count($answer) * 3;
            $translatearray = array(
                "model" => "gpt-3.5-turbo",
                "messages" => array(
                    array(
                        "role" => "system", 
                        "content" => "Translate this from " . $language . " to English" 
                    ),
                    array(
                        "role" => "user", 
                        "content" => $answer
                    )
                ),
                "max_tokens" => $tokens,
                "temperature" => 0.0,
            );

            // Send the request
            echo " -- Token Max: " . $tokens . " --\n";
            echo "Translating answer from " . $language . ".....";
            
            $answer = sendRequest($translatearray, $key, $questionobject->id);

            echo "Translation response: \n" . $answer;
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
            "max_tokens" => 2,
            "temperature" => 0.0
        );
        // 
        $grade = sendRequest($requestarray, $key, $questionobject->id);
        if ($grade == "Pass." || $grade == "Fail.") {
            echo $grade;
            updateGrade($grade, $questionobject);
        }
        else {
            echo "Invalid response recieved";
        }
    }
}

// Run the request
buildQuery($attemptid);
?>
