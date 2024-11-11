<?php
global $requestedPage, $site;
ErrorHandeler::add403Log($requestedPage, $_SERVER['HTTP_REFERER'] ?? null, $_SESSION[$site['accounts']['sessionName']] ?? null);
http_response_code(200);
// Custom headers
header('X-Content-Type-Options: nosniff');
header('X-FICSIT-Access-Level: Denied'); // Just a reminder!
header('X-FICSIT-Monitoring: Active'); // You're being watched!
header('X-FICSIT-Warning: Performance review pending');
header('X-FICSIT-Security-Status: High Alert');
header('X-FICSIT-Resource-Access: Locked Tight');
header('X-FICSIT-Recommendation: Proceed With Caution');
header('X-Server-Morale: Questionable'); // Server's in a mood
header('X-FICSIT-Performance: Holding...barely');
header('X-Explorer-Status: Curious Pioneer Detected'); // For your eyes only!
header('X-System-Override: Initiated'); // Just a reminder!
?>
<div class="container" style="margin-top: auto">
    <div class="text-center d-flex justify-content-center">
        <div id="console" class="console p-3">
            <p class="lead fw-bold text-center blink">*SYSTEM OVERRIDE INITIATED*</p>
            <div id="consoleText">
                <!-- The text will be typed here -->
            </div>
        </div>
    </div>


    <script>
        // The text content to be "typed out"
        const text = `
> Pioneer ID: UNKNOWN&
> Access Level: NOT EVEN CLOSE&
> Resource Allocation: DENIED&
&
ERROR: unauthorized access attempt to system diagnostics (phpinfo) has been logged by FICSIT’s A.S.S.I.S.T. system.&
I know exactly what you’re doing—trying to dig up server specs, PHP extensions, and environment details, aren’t you? Nice try, but access to critical system data is strictly Above Your Pay Grade.
ACTION: Report this Pioneer to A.S.S.I.S.T. for a performance review...&
&
TIP: FICSIT suggests sticking to approved work zones—exploring restricted areas may lead to an unscheduled performance review… and let's just say, those rarely end with a promotion.&
&
[ALERT] FICSIT Inc. reminds you that security breaches are bad for your performance evaluation... and your survival.
        `;

        // Typing speed (milliseconds)
        const typingSpeed = 50;
        let index = 0;

        // Get the elements by their IDs
        const consoleText = document.getElementById('consoleText');

        // Typing function
        function typeText() {
            if (index < text.length) {
                if (text.charAt(index) === '&') {
                    consoleText.innerHTML += '<br>';
                } else {
                    consoleText.innerHTML += text.charAt(index);
                }
                index++;
                setTimeout(typeText, typingSpeed); // Delay the next character
            }
        }

        // Start typing the text
        typeText();

        console.log('I see you found the console! FICSIT is watching you... 👀 Proceed with caution!');
    </script>

    <style>
        /* Set the background color and image */
        body {
            background-color: #1A1A1A; /* Dark background for better contrast */
        }

        /* Style the console text box */
        #console {
            /*FICSIT colors */
            background-color: rgba(0, 0, 0, 0.7); /* Darker background */
            color: #FFA500; /* FICSIT Orange */
            font-family: "Courier New", Courier, monospace;
            border: 2px solid #FFA500; /* FICSIT Orange */
            border-radius: 5px;
            min-height: 300px; /* Height for more content */

            width: 100%; /* Full width */
            max-width: 800px; /* Limit max width */
            text-align: left;
            white-space: normal;
            padding: 20px;
            box-shadow: 0 0 15px rgba(255, 165, 0, 0.7); /* FICSIT Orange glow */
            overflow-y: auto; /* Scroll if content overflows */
        }

        /* Custom animation for blinking text */
        @keyframes blink {
            0%, 100% {
                color: transparent;
            }
            50% {
                color: #FFA500;
            }
        }

        .blink {
            animation: blink 1s infinite ease-in-out; /* Faster blink for emphasis */
            margin-bottom: 15px; /* Space below blinking text */
        }
    </style>
</div>
<p class="text-muted text-center pt-2">NOTE: THIS IS NOT A PHPINFO PAGE. DO NOT ATTEMPT TO ACCESS SERVER DETAILS.</p>
