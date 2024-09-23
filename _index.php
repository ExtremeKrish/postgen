<?php
// Telegram bot access token

echo file_get_contents("https://api.telegram.org/bot7371455613:AAGKAnpEHTpPaRU6O8g5T_h09_GEvrLJ7TI/sendMessage?chat_id=6162808595&text=Hello");

define("BOT_TOKEN", "7371455613:AAGKAnpEHTpPaRU6O8g5T_h09_GEvrLJ7TI");
// Base URL for Telegram API
define("API_URL", "https://api.telegram.org/bot" . BOT_TOKEN . "/");

// Function to send a message
function sendMessage($chat_id, $text, $reply_markup = null) {
    $url = API_URL . "sendMessage?chat_id=$chat_id&text=" . urlencode($text);
    if ($reply_markup) {
        $url .= "&reply_markup=" . json_encode($reply_markup);
    }
    file_get_contents($url);
}

// Function to send an image with buttons
function sendImage($chat_id, $text, $message_id) {
    $data = loadData();

    // Generate the image URL based on user's text
    $photo_url = "https://thedarkintrigue.com/new/img.php?bgid=" . $data[$chat_id]['selected_item'] . "&text=" . urlencode($text);
    // Message content with invisible character for embedding the image link
    $message = <<<TEXT
Alright, here the image generated:
$photo_url
<a href="$photo_url"> ‏ </a>
TEXT;
    // Inline keyboard markup for buttons
    $keyboard = ["inline_keyboard" => [[["text" => "Accept", "callback_data" => "accept_"], ["text" => "Reject", "callback_data" => "reject_"]]]];
    // Encode the keyboard array into JSON
    $encodedKeyboard = json_encode($keyboard);
    // Prepare the post fields
    $postFields = ["chat_id" => $chat_id, "text" => $message, "parse_mode" => "HTML", "reply_markup" => $encodedKeyboard, "disable_web_page_preview" => false];
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL . "sendMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
    // Execute cURL request and check for errors
    $result = curl_exec($ch);
    if (!$result) {
        error_log("Curl error: " . curl_error($ch));
    }
    curl_close($ch);
}

function deleteMessage($chat_id, $message_id) {
    $postFields = ["chat_id" => $chat_id, "message_id" => $message_id];
    // Initialize cURL session
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, API_URL . "deleteMessage");
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($postFields));
    // Execute cURL request and check for errors
    $result = curl_exec($ch);
    if (!$result) {
        error_log("Curl error: " . curl_error($ch));
    }
    curl_close($ch);
}

// Function to save data to JSON file
function saveData($data) {
    file_put_contents('data.json', json_encode($data));
}

// Function to load data from JSON file
function loadData() {
    if (!file_exists('data.json')) {
        return [];
    }
    return json_decode(file_get_contents('data.json'), true);
}

// Get the input
$input = file_get_contents('php://input');
$update = json_decode($input, true);

$msgg = $update['message'];
$chat_id = $update['message']['chat']['id'];
$message = $update['message']['text'];
$data = loadData();

if ($message == '/start') {
    sendMessage($chat_id, "Please provide your token:");
} elseif ($message == '/deldata') {
    unset($data[$chat_id]);
    saveData($data);
    $url = API_URL . "messages.deleteChatUser?chat_id=$chat_id&revoke_history=true";
    sendMessage($chat_id, "Deleted. Now send /start");
} elseif ($message == '/setcaption') {
    // Set flag to indicate the user is setting a caption
    $data[$chat_id]['setting_caption'] = true;
    saveData($data);
    sendMessage($chat_id, "Please send the caption text:");
} elseif (isset($data[$chat_id]['setting_caption']) && $data[$chat_id]['setting_caption']) {
    $acc = $data[$chat_id]['selected_item'];
    // Save the caption text
    $data[$chat_id][$acc . 'caption'] = $message;
    unset($data[$chat_id]['setting_caption']); // Reset the flag
    saveData($data);
    sendMessage($chat_id, "Caption saved!");

} elseif ($message == '/changeacc') {
    $token = $data[$chat_id]['token'];
    $items = file_get_contents("https://graph.facebook.com/v19.0/me/accounts?access_token=" . $token);
    $response_data = json_decode($items, true);
    // Initialize an empty array to hold the name and id pairs
    $items = [];
    // Iterate through the data array and extract the name and id
    foreach ($response_data["data"] as $item) {
        $items[] = ["name" => $item["name"], "id" => $item["id"]];
    }
    // End
    // Create inline buttons
    $buttons = [];
    foreach ($items as $item) {
        $buttons[] = [['text' => $item['name'], 'callback_data' => $item['id']]];
    }
    $reply_markup = ['inline_keyboard' => $buttons];
    sendMessage($chat_id, "Select an item:", $reply_markup);
} elseif ($message == '/setbg') {
    // Set flag to indicate the user is setting a background image
    $data[$chat_id]['setting_bg'] = true;
    saveData($data);
    sendMessage($chat_id, "Please send the image file as a document in 3000*3000px:");
} elseif (isset($data[$chat_id]['setting_bg']) && $data[$chat_id]['setting_bg']) {
    if (isset($update['message']['document'])) {
        $file_id = $update['message']['document']['file_id'];
        $file_path = getFilePath($file_id);
        $image_data = file_get_contents("https://api.telegram.org/file/bot" . BOT_TOKEN . "/" . $file_path);
        
        $selected_item_number = $data[$chat_id]['selected_item'];
        if (!file_exists('bgs')) {
            mkdir('bgs', 0777, true);
        }
        file_put_contents("bgs/$selected_item_number", $image_data);

        unset($data[$chat_id]['setting_bg']); // Reset the flag
        saveData($data);
        sendMessage($chat_id, "Background image saved!");
    } else {
        sendMessage($chat_id, "Please send an image file as a document.");
    }
} elseif (!isset($data[$chat_id])) {
    // Save the token
    $data[$chat_id] = ['token' => $message];
    saveData($data);
    // Call external API to get items
    $token = $data[$chat_id]['token'];
    $items = file_get_contents("https://graph.facebook.com/v19.0/me/accounts?access_token=" . $token);
    $response_data = json_decode($items, true);
    // Initialize an empty array to hold the name and id pairs
    $items = [];
    // Iterate through the data array and extract the name and id
    foreach ($response_data["data"] as $item) {
        $items[] = ["name" => $item["name"], "id" => $item["id"]];
    }
    // End
    // Create inline buttons
    $buttons = [];
    foreach ($items as $item) {
        $buttons[] = [['text' => $item['name'], 'callback_data' => $item['id']]];
    }
    $reply_markup = ['inline_keyboard' => $buttons];
    sendMessage($chat_id, "Select an item:", $reply_markup);
} elseif (isset($update['callback_query'])) {
    $callback_query = $update['callback_query'];
    $user_id = $callback_query['from']['id'];
    $item_id = $callback_query['data'];
    if (endsWith($item_id, '_')) {
        if ($item_id == "reject_") {
            // Delete the message on reject action
            unset($data[$user_id]['uploading']);
            saveData($data);
            deleteMessage($user_id, $callback_query["message"]["message_id"]);
        } elseif ($item_id == "accept_") {
            $token = $data[$user_id]['token'];
            $acc = $data[$user_id]['selected_item'];
            $caption = $data[$user_id][$acc . 'caption'];
            $bid = $data[$user_id]['selected_item'];
            sendMessage($user_id, 'Uploading.....');
            $img_url = "https://thedarkintrigue.com/new/img.php?bgid=" . $data[$user_id]['selected_item'] . "&text=" . $data[$user_id]['uploading'];
            $url2 = "https://graph.facebook.com/v20.0/$bid/media";
            // ?access_token=$token&image_url=$img_url&caption=$caption";
            // Image Upload
        $datau = array('image_url' => $img_url, 'access_token' => $token, 'caption' => $caption);
           
            $resp3 = reqpost($url2, $datau);
           // sendMessage($user_id, json_encode($datau));
           // sendMessage($user_id, $url2);
            if (!isset($resp3['id'])) {
                sendMessage($user_id, json_encode($resp3));
            }
            $creation_id = $resp3['id'];
            $url3 = "https://graph.facebook.com/v19.0/$bid/media_publish";
            $data2 = array('creation_id' => $creation_id, 'access_token' => $token);
            $done = reqpost($url3, $data2);
            if (!isset($done['id'])) {
                sendMessage($user_id, "Failed to publish media on Instagram.");
            } else {
                sendMessage($user_id, "Successfully Uploaded!");
            }
        }
    } else {
        // Save the selected item
        $token = $data[$user_id]['token'];
        $url1 = "https://graph.facebook.com/v19.0/" . $item_id . "?fields=instagram_business_account&access_token=" . $token;
        // Getting business ID
        $bid = req($url1);
        $data[$user_id]['selected_item'] = $bid['instagram_business_account']['id'];
        saveData($data);
        sendMessage($user_id, "Saved!");
    }
} else {
    // Normal Messages
    sendImage($chat_id, $message, $msgg["message_id"]);
    $data[$chat_id]['uploading'] = $message;
    saveData($data);
}

function getFilePath($file_id) {
    $url = API_URL . "getFile?file_id=" . $file_id;
    $result = json_decode(file_get_contents($url), true);
    return $result['result']['file_path'];
}

function req($url) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json',));
    $response = curl_exec($ch);
    curl_close($ch);
    return json_decode($response, true);
}

function reqpost($urlo, $datao) {
    $ch1 = curl_init($urlo);
    curl_setopt($ch1, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch1, CURLOPT_POST, true);
    curl_setopt($ch1, CURLOPT_POSTFIELDS, http_build_query($datao));
    $response2 = curl_exec($ch1);
    curl_close($ch1);
    $decoded_response = json_decode($response2, true);
    return $decoded_response;
}

function endsWith($haystack, $needle) {
    $length = strlen($needle);
    if (!$length) {
        return true;
    }
    return substr($haystack, -$length) === $needle;
}
?>
