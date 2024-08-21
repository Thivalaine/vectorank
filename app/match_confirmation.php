<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Confirmation</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f8f9fa;
            color: #343a40;
            text-align: center;
            margin: 0;
            padding: 0;
        }

        .container {
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            height: 100vh;
        }

        h1 {
            font-size: 2rem;
            margin-bottom: 10px;
        }

        p {
            font-size: 1.2rem;
            margin-bottom: 20px;
        }

        .loader {
            border: 8px solid #f3f3f3;
            border-radius: 50%;
            border-top: 8px solid #28a745;
            width: 50px;
            height: 50px;
            animation: spin 1s linear infinite;
            margin-bottom: 20px;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="loader"></div>
        <h1>Les données ont été traitées avec succès.</h1>
        <p>Vous serez redirigé vers la page principale dans quelques instants...</p>
    </div>

    <script>
        // Script pour vider le localStorage
        document.addEventListener('DOMContentLoaded', function() {
            localStorage.removeItem('matches');
            // Redirection vers la page principale après avoir vidé le localStorage
            setTimeout(function() {
                window.location.href = 'index.php'; // Changez cela en la page vers laquelle vous voulez rediriger
            }, 1000); // Temps d'attente avant la redirection (en ms)
        });
    </script>
</body>
</html>
