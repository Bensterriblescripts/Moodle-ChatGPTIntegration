<?php

include_once 'libOpenAI.php';

/* Cases */
function testAssess($assessQuestion, $assessAnswer) {
    if (!$grade = llmTextAssess($assessQuestion, $assessAnswer)) {
        writeDebugLog("Testing || Assessing Failed.");
    }
    else {
        writeDebugLog("Testing || Assessing Result: " . $grade);
    }
}
function testTranslate($translateInput, $translateLanguage) {
    if (!$translation = llmTextTranslate($translateInput, $translateLanguage)) {
        writeDebugLog("Testing || Translation Failed.");
    }
    else {
        writeDebugLog("Testing || Translation Result: " . $translation);
    }
}
function testImage($imageURL, $imageQuestion) {
    if (!$imageDescription = llmImageDescribe($imageURL, $imageQuestion)) {
        writeDebugLog("Testing || Image Description Failed.");
    }
    else {
        writeDebugLog("Testing || Image Description Result: " . $imageDescription);
    }
}

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
if ($translateTest) { testTranslate($translateInput, $translateLanguage); }
if ($imageTest) { testImage($imageURL, $imageQuestion); }


?>