<?php

require_once("dbcreds.php");
require_once("helper.php");
require_once("telegramfunctions.php");

## Get Telegram Input
$tg = json_decode(file_get_contents("php://input"));
$chatid = null; ## GLOBAL USED

## We are not going to entertain input other than message
if(!isset($tg->message)) exit();

## Extract chat id
$chatid = $tg->message->chat->id;

## Getting / creating user session
$user_session = getUserSession($chatid);

## Setting up for Request Payload
$POSTData = [
    "sessionId" => $user_session->session_id,
    "senderId" => $user_session->sender_id,
    "messageId" => generateRandomID(),
    "userType" => "user",
    "messageType" => "text",
    "language" => $user_session->language
];

## Check if it is text
if(isset($tg->message->text)){

    ## Display to user: "Typing..."
    tgSendAction($chatid, "typing");

    ## check if the text is /start
    if($tg->message->text == "/start"){
        ## We create or restart the session
        clearAllUserSession($chatid);
        $user_session = createUserSession($chatid);

        $POSTData["text"] = "Hi";
        $POSTData["textLength"] = 2;    
    }else{
        $POSTData["text"] = $tg->message->text;
        $POSTData["textLength"] = strlen($tg->message->text);
    }

    ## Fetch for response and process it
    $response = pushPutriPLUSAPI($POSTData);
    pushPutriPLUSAPIToTelegram($response);
}

## Check if we receive location!
else if(isset($tg->message->location)){
    ## We get the latlong
    $lat = strval($tg->message->location->latitude);
    $long = strval($tg->message->location->longitude);

    $text = "$lat,$long";
    $POSTData["text"] = $text;
    $POSTData["textLength"] = strlen($text);

    ## Fetch for response and process it
    $response = pushPutriPLUSAPI($POSTData);
    pushPutriPLUSAPIToTelegram($response);
}

## Making request to Putri PLUS API
function pushPutriPLUSAPI($data){
    /**
     * Removed to prevent abusive to the API
     */
}

## Function to Push Putri PLUS API to Telegram
function pushPutriPLUSAPIToTelegram($response){
    global $chatid;

    ## Since the response we received in array, we need to use loop to process it
    ## Different situation has different number of response (thats why we need to use loop)
    for($i=0; $i< count($response); $i++){
        ## From here, we can check what kind of response we receive
        ## So far, the possible responses are text, image and quickReplies
        ## Lets do the checking!
        ## But wait, quickReplies is a button. And we would like to attach button along with the last text message since that is the Telegram API requirement.
        ## Hmmmm, how? (tbc: A)
        if(isset($response[$i]->text)){
            ## If the response is text
            ## Get the text!
            $text = $response[$i]->text->text[0];

            ## If there is <br> tag on text, we replace it with \n
            ## Since <br> is not supported by Telegram parse_mode
            ## Read more on: https://core.telegram.org/bots/api#formatting-options
            $text = str_replace(["<br>", "<br/>"], "\n", $text);

            ## If the text is equal to something as follows, we know it is "background message" for the bot
            if($text == "[#TPS]"){
                continue;
            }else if($text == "[#C_MS]"){
                updateUserLanguage($chatid, "ms");
                continue;
            }else if($text == "[#C_EN]"){
                updateUserLanguage($chatid, "en");
                continue;
            }

            
            ## (cont: A) Nevermind, fix it.
            ## Initing keyboard
            $keyboard = [];

            ## We will going to check if the next response is quickReplies or not.
            ## Because, if it is, we know it is last text (I guess. haha)
            if(isset($response[$i+1]->quickReplies)){
                ## If it is QuickReplies (button on the website)
                ## We get the button
                $quickReplies = $response[$i+1]->quickReplies->quickReplies;

                $keyboard["keyboard"] = [];
                $keyboard["resize_keyboard"] = true;
                $keyboard["one_time_keyboard"] = true;

                ## Add the button!
                foreach($quickReplies as $buttonText){
                    ## Check if the button actually ask for user location
                    ## Because on Telegram, we have an API for that!
                    if($buttonText == "Share my location" || $buttonText == "Kongsi lokasi saya") $keyboard["keyboard"][] = [["text" => "$buttonText", "request_location" => true]];
                    else $keyboard["keyboard"][] = [["text" => "$buttonText"]];
                }

                ## If the script entering this scope, we need to plus 1 to the iteration since we already read the next data
                $i++;
            }

            ## Send the message!
            tgReplyText($chatid, $text, $keyboard);

        }else if(isset($response[$i]->image)){
            ## If the response is image
            ## Get the image URL
            $imageURL = $response[$i]->image->imageUri;

            ## Showing to user: "Sending Photo..."
            tgSendAction($chatid, "upload_photo");

            ## Send the image!
            tgSendPhoto($chatid, $imageURL);
        }
    }

    ## If the API response quickReplies only
    if(isset($response[0]->quickReplies)){
        ## Initing keyboard and text
        $keyboard = [];
        $text = "More";

        ## If it is QuickReplies (button on the website)
        ## We get the button
        $quickReplies = $response[0]->quickReplies->quickReplies;

        $keyboard["keyboard"] = [];
        $keyboard["resize_keyboard"] = true;
        $keyboard["one_time_keyboard"] = true;

        ## Add the button!
        foreach($quickReplies as $buttonText){
            ## Check if the button actually ask for user location
            ## Because on Telegram, we have an API for that!
            if($buttonText == "Share my location" || $buttonText == "Kongsi lokasi saya") $keyboard["keyboard"][] = [["text" => "$buttonText", "request_location" => true]];
            else $keyboard["keyboard"][] = [["text" => "$buttonText"]];
        }

        tgReplyText($chatid, $text, $keyboard);
    }
}

## Get user session
function getUserSession($chatid){
    global $db;

    $get_session = pg_query($db, "SELECT session_id, sender_id, language, last_update FROM sessions WHERE tg_chat_id=$chatid");
    if($get_session && pg_num_rows($get_session) == 1){
        $sess = pg_fetch_object($get_session);

        $now_time = time();

        ## Check if the session is still valid (time < 30 minutes)
        if(($now_time - intval($sess->last_update)) <= 1800){

            ## We update "last_update" column on database for this user
            pg_query($db, "UPDATE sessions SET last_update=$now_time WHERE tg_chat_id=$chatid");

            return ((Object)[
                "in_session" => true,
                "chat_id" => $chatid,
                "session_id" => $sess->session_id,
                "sender_id" => $sess->sender_id,
                "language" => $sess->language,
                "last_updated" => $now_time
            ]);
        }else{
            global $tg;
            $tg->message->text = "Hi";
            tgReplyText($chatid, "Session expired. Restarting...");
            clearAllUserSession($chatid);
            return createUserSession($chatid);
            
        }
    }else{
        clearAllUserSession($chatid);
        return createUserSession($chatid);
    }
}
## Create new user session
function createUserSession($chatid){
    global $db;
    
    $sessionId = generateRandomID();
    $senderId = generateRandomID();
    $last_update = time();

    ## Insert into user session
    $new_session = pg_query($db, "INSERT INTO sessions (tg_chat_id, session_id, sender_id, language, last_update) VALUES ($chatid, '$sessionId', '$senderId', 'en', $last_update)");
    if($new_session){
        return ((Object)[
            "in_session" => true,
            "chat_id" => $chatid,
            "session_id" => $sessionId,
            "sender_id" => $senderId,
            "language" => "en",
            "last_updated" => $last_update
        ]);
    }else{
        return ((Object)[
            "in_session" => false
        ]);
    }
}

## Clear user session
function clearAllUserSession($chatid){
    global $db;

    ## Delete all session from chat_id
    $is_cleared = pg_query($db, "DELETE FROM sessions WHERE tg_chat_id=$chatid");
    if($is_cleared) return true;
    return false;
}

## update User Language
function updateUserLanguage($chatid, $lang){
    global $db;
    global $POSTData;
    
    $lang = pg_escape_string($lang);

    $is_updated_lang = pg_query($db, "UPDATE sessions SET language='$lang' WHERE tg_chat_id=$chatid");
    if($is_updated_lang){
        $POSTData["language"] = "$lang";
        return true;
    }
    return false;
}

?>