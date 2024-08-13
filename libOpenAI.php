<?php

$DB = null;
$env = 'uat';
$debug = true;
$apiKey = getenv("OPENAI_API_KEY");

/* Logging */
function writeDebugLog($errorMessage) {
    global $env, $debug;
    if ($debug) {
        if ($env == 'prod') {
            // Log prod folder
            // file_put_contents(datetime . " || " . $errorMessage);
        }
        else if ($env == 'uat') {
            // Log uat folder
            // file_put_contents(datetime . " || " . $errorMessage);
        }
        echo $errorMessage;
    }
}
function writeErrorLog($errorMessage) {
    global $env;

    if ($env == 'prod') {
        // Log prod folder
        // file_put_contents(datetime . " || " . $errorMessage);
    }
    else if ($env == 'uat') {
        // Log uat folder
        // file_put_contents(datetime . " || " . $errorMessage);
    }
}

/* Queries */
function llmBuildQuery($queryType, $input, $conditions) {
    global $DB, $apiKey;

    // Translation
    if ($queryType == 'translate') {
        $tokens = str_word_count($input) * 3;
        $prompt = array(
            "model" => "gpt-3.5-turbo",
            "messages" => array(
                array(
                    "role" => "system", 
                    "content" => "Translate this from " . $conditions . " to English" 
                ),
                array(
                    "role" => "user", 
                    "content" => $input
                )
            ),
            "max_tokens" => $tokens,
            "temperature" => 0.0
        );
    }

    // Assess
    else if ($queryType == 'assess') {
        $promptString = "Question:\n" . $conditions . "\nAnswer:\n" . $input;
        $prompt = array(
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

    // Image Description
    else if ($queryType == 'image') {
        $prompt = array(
            "model" => "gpt-3.5-turbo",
            "messages" => array(
                array(
                    "role" => "system", 
                    "content" => "Tell me how this image relates to the question '$conditions' or if it has very little relation."
                ),
                array(
                    "role" => "user", 
                    "content" => $input
                )
            ),
            "max_tokens" => 5,
            "temperature" => 0.0
        );
    }
    else {
        return false;
    }

    // Send it
    $result = "";
    $url = "https://api.openai.com/v1/chat/completions";
    $jsonData = json_encode($prompt);
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        "Content-Type: application/json",
        "Authorization: Bearer " . $apiKey
        )
    );
    curl_setopt($curl, CURLOPT_POST, true);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
    $result = curl_exec($curl);
    if ($result === false) {
        $error = curl_error($curl);
        writeErrorLog("buildQuery || Curl error: " . $error);
    } 
    else {
        $statusCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        if ($statusCode >= 200 && $statusCode < 300) {
            $responseArray = json_decode($result, true);
            $content = $responseArray['choices'][0]['message']['content'];
            return $content;
        }
        else if ($statusCode == 401) {
            writeErrorLog("buildQuery || Unauthorized, you may need a new API key.");
            return false;
        }
        else if ($statusCode == 429) {
            writeErrorLog("buildQuery || Too many requests made to the API.");
            return false;
        }
        else {
            writeErrorLog("buildQuery || Request failed with status code: " . $statusCode);
            return false;
        }
    }
    curl_close($curl);
}

/* Inputs */
function llmTextAssess($question, $answer) {
    if (!$result = llmBuildQuery('assess', $question, $answer)) {
        return false;
    }

    $sendit = true;
    $retries = 0;
    $endResult = "";

    // Loop 3 times or until an answer is "Pass" or "Fail"
    while ($sendit == true && $retries < 3) {
        if (!$result = llmBuildQuery('assess', $question, $answer)) {
            return false;
        }
        $resultLowercase = strtolower($result);
        if (strpos($resultLowercase, "pass") !== false) {
            $endResult = "Pass";
            $sendit = false;
        }
        else if (strpos($resultLowercase, "fail") !== false) {
            $endResult = "Pass";
            $sendit = false;
        }
        else {
            writeDebugLog("inputTextAssess || Invalid response '$result', retrying.");
            $retries++;
        }
    }

    if (!empty($endResult)) {
        return $endResult;
    }
    else {
        return false;
    }
}

function llmTextTranslate($text, $language) {
    if (!$result = llmBuildQuery('translate', $text, $language)) {
        return false;
    }
    return $result;
}

function llmImageDescribe($image, $conditions) {
    if (!$result = llmBuildQuery('image', $image, $conditions)) {
        return false;
    }
    return $result;
}

?>