<?php

include_once 'libOpenAI.php';
$consoleEnabled = false;

/* In case we need to test both */
if ($consoleEnabled) {
    $consoleConfirm = readline('Input Type | h - hardcoded, c - console: ');
    if ($consoleConfirm == "c") { $consoleTest = true; }
    else if ($consoleConfirm == "h") { $consoleTest = false; }
}

/* Cases */
function testAssess($assessQuestion, $assessAnswer) {
    if (!$grade = inputTextTranslate($assessQuestion, $assessAnswer)) {
        writeDebugLog("Testing || Assessing Failed.");
    }
    else {
        writeDebugLog("Testing || Assessing Result: " . $grade);
    }
}
function testTranslate($translateInput, $translateLanguage) {
    if (!$translation = inputTextTranslate($translateInput, $translateLanguage)) {
        writeDebugLog("Testing || Translation Failed.");
    }
    else {
        writeDebugLog("Testing || Translation Result: " . $translation);
    }
}
function testImage($imageURL, $imageQuestion) {
    if (!$imageDescription = inputTextTranslate($imageURL, $imageQuestion)) {
        writeDebugLog("Testing || Image Description Failed.");
    }
    else {
        writeDebugLog("Testing || Image Description Result: " . $imageDescription);
    }
}

/* Hard-Coded Tests */
if (!$consoleTest) {

    // Testing Vars
    $assessQuestion = "What is the distance between the earth and the sun?";
    $assessAnswer = "151 million km";
    $assessTest = true;

    $translateInput = "";
    $translateLanguage = "";
    $translateTest = false;

    $imageURL = "";
    $imageQuestion = "";
    $imageTest = false;

    // Run Tests
    if ($assessTest) { testAssess($assessQuestion, $assessAnswer); }
    if ($assessTranslate) { testTranslate($translateInput, $translateLanguage); }
    if ($imageTest) { testImage($imageURL, $imageQuestion); }

}

/* Console Input Tests */
if ($consoleTest) {
    $type = readline("Test Type || 0 - Assess, 1 - Translate, 2 - Image: ");

    // Input/Prompt Types
    if ($type == "0") { 
        $question = readline("Question: ");
        $answer = readline("Answer: ");
    }
    else if ($type == "1") {
        $language = readline("Language: ");
        $text = readline("Text: ");
        testTranslate($text, $language);
    }
    else if ($type == "2") {
        $url = readline("Image URL: ");
        $question = readline("Question: ");
        testImage($url, $question);
    }
}

?>