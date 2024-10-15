<?php
http_response_code(403);
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
ERROR: Unauthorized personnel attempting to access restricted data!&
ACTION: Report this Pioneer to A.S.S.I.S.T. for a performance review...&
&
TIP: Conveyor belts and jump pads won't help you here. Suggest returning to your designated task area.&
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
