<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newContent = $_POST['prompt_content'] ?? '';
    file_put_contents('../prompt.txt', $newContent);
    $message = "File updated successfully!";
    header("Location: ../");
}

$content = file_exists('../prompt.txt') ? file_get_contents('../prompt.txt') : '';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Prompt</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8fafc;
            color: #1f2937;
            margin: 0;
            padding: 0;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }

        .login-screen {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(31, 41, 55, 0.9);
            display: flex;
            justify-content: center;
            align-items: center;
        }

        .login-container {
            background: #fff;
            padding: 20px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 100%;
            max-width: 400px;
            text-align: center;
        }

        .login-container input {
            width: 100%;
            padding: 10px;
            margin: 10px 0;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
        }

        .login-container button {
            background-color: #e91e63;
            color: #fff;
            font-size: 1rem;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        .login-container button:hover {
            background-color: #d01754;
        }
        .container {
            background: #fff;
            padding: 20px 5%;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            width: 60%;
            max-width: 100vw;
            margin:0px 10px;
            display:none;
        }

        h1 {
            color: #e91e63;
            font-size: 1.5rem;
            text-align: center;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 15px;
        }

        label {
            font-size: 1rem;
            color: #1f2937;
        }

        textarea {
            width: 95%;
            height: 50VH;
            padding: 10px;
            font-size: 1rem;
            border: 1px solid #ddd;
            border-radius: 5px;
            outline: none;
            resize: none;
        }

        textarea:focus {
            border-color: #e91e63;
        }

        button {
            background-color: #e91e63;
            color: #fff;
            font-size: 1rem;
            padding: 10px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            transition: background-color 0.3s ease;
        }

        button:hover {
            background-color: #d01754;
        }

        .message {
            color: green;
            font-size: 0.9rem;
            text-align: center;
        }
        @media (max-width:780px) {
            .container {
                width: 100%;
            }
            
        }
    </style>
    <script>
        document.addEventListener("DOMContentLoaded", () => {
            const loginScreen = document.querySelector(".login-screen");
            const mainContainer = document.querySelector(".container");
            const pinInput = document.querySelector("#pin");
            const loginButton = document.querySelector("#loginButton");

            loginButton.addEventListener("click", () => {
                if (pinInput.value === "574231") {
                    loginScreen.style.display = "none";
                    mainContainer.style.display = "block";
                } else {
                    alert("Invalid PIN. Please try again.");
                }
            });
        });
    </script>
</head>
<body>
    <div class="login-screen">
        <div class="login-container">
            <h1>Enter PIN</h1>
            <input type="password" id="pin" placeholder="Enter PIN">
            <button id="loginButton">Login</button>
        </div>
    </div>

    <div class="container">
        <h1>Edit Prompt File</h1>
        <?php if (isset($message)): ?>
            <p class="message"><?= htmlspecialchars($message) ?></p>
        <?php endif; ?>

        <form method="POST">
            <label for="prompt_content">Edit the content of prompt.txt:</label>
            <textarea id="prompt_content" name="prompt_content"><?= htmlspecialchars($content) ?></textarea>
            <button type="submit">Save Changes</button>
        </form>
    </div>
</body>
</html>
