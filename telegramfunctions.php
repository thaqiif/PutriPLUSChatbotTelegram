<?php

/**
 *    Telegram functions (API)
 */

## Push any method with data to Telegram bot API
function tgPush($method, $dataArray){
    global $bottoken;

    $ch = curl_init("https://api.telegram.org/bot$bottoken/$method");
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $dataArray);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array("Content-Type" => "application/x-www-form-urlencoded", "charset" => "UTF-8"));
    $r = json_decode(curl_exec($ch));
    curl_close($ch);
    return $r;
}

## Telegram: sendMessage
function tgReplyText($chatid, $text, $keyboard = []){
    return tgPush("sendMessage", [
        "chat_id" => $chatid,
        "text" => $text,
        "parse_mode" => "HTML",
        "reply_markup" => !empty($keyboard) ? json_encode($keyboard) : ''
    ]);
}

## Telegram sendPhoto
function tgSendPhoto($chatid, $photoURL){
    return tgPush("sendPhoto", [
        "chat_id" => $chatid,
        "photo" => $photoURL
    ]);
}

function tgSendAction($chatid, $action){
    return tgPush("sendChatAction", [
        "chat_id" => $chatid,
        "action" => $action
    ]);
}

?>