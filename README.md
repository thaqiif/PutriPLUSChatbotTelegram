# Putri PLUS Chatbot on Telegram Messenger (Unofficial)

![Putri PLUS Chatbot Logo](/images/Putri%20PLUS%20Chatbot%20Logo.jpg?raw=true)
Unofficial Putri PLUS Chatbot on Telegram Messenger. 

You can interact with Putri PLUS Chatbot (Beta version) that available on the [PLUS Website](http://www.plus.com.my/) as well as their mobile application. I took the opportunity to expand their great chatbot by integrating it with Telegram Messenger Chatbot. Try it here 👉 [Putri PLUS Chatbot (Unofficial)](https://t.me/putripluschatbot)

All the code intended for educational purpose only. I removed Putri PLUS API used in the real Telegram bot to prevent abusive to their system. If you want to make this code works, you need to figure out on your own.

Every details and flow of the algorithm as written in comment in every code section (hopefully). Happy reading! 😁

😋 *Code first write on June 13, 2020 4:07PM*
👌 *Code last updated on June 13, 2020 9:49PM*

## What is Putri PLUS?

PUTRI, which stands for PLUS Texting Realtime Interface and can also be considered as the highway industry’s first ever chatbot.

"PUTRI is designed to interact with PLUS highway customers. Over time, PLUS hopes that the interactive conversations through its Artificial Intelligence and Machine Learning model will allow PUTRI to handle over 70% of the 1,500 calls that their Traffic Monitoring Centre (TMC) receives averagely per day." *[- SoyaCincau](https://www.soyacincau.com/2020/05/22/plus-highway-introduces-putri-their-first-highway-customer-chatbot/)*

## How-to-use on Telegram Chatbot

> Send /start to start or restart current session on Telegram Messenger

## Database

I created database to store all users chat session. It only lasts for 30 minutes counting from last activity time. I'm using PostgreSQL anyway. The table and column as follows:

**Table Name:** sessions
| Name        | Type    | Notes                           |
|-------------|---------|---------------------------------|
| rid         | integer | Auto increment                  |
| tg_chat_id  | integer | Store Telegram user ID          |
| session_id  | text    |                                 |
| sender_id   | text    |                                 |
| language    | text    |                                 |
| last_update | integer | UNIX Timestamp of last activity |

## Screenshot

![Screenshot 1](/images/Screenshot%201.jpg?raw=true)
![Screenshot 2](/images/Screenshot%202.jpg?raw=true)
![Screenshot 3](/images/Screenshot%203.jpg?raw=true)

## Legal

This code is in no way affiliated with, authorized, maintained, sponsored or endorsed by PLUS or any of its affiliates or subsidiaries. This is an independent and unofficial code. Use at your own risk.