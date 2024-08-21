<?php
    $pageTitle = "Ajout d'un match"; 
    include('header.php'); 
?>

<div class="container-fluid">
<style>
    .card {
        padding: 30px;
        border-radius: 15px;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
    }
    .btn-primary {
        background-color: #28a745;
        border-color: #28a745;
    }
    .btn-primary:hover {
        background-color: #218838;
        border-color: #218838;
    }
    .btn-secondary {
        background-color: #6c757d;
        border-color: #6c757d;
    }
    h1 {
        font-size: 2.5rem;
        color: #343a40;
        text-align: center;
    }
    label {
        font-weight: bold;
        color: #495057;
    }
    .form-control {
        border-radius: 10px;
        padding: 10px;
    }
    .form-group {
        margin-bottom: 20px;
    }
    .match-form {
        border: 2px solid #28a745;
        border-radius: 10px;
        padding: 20px;
        margin-top: 20px;
        position: relative;
        background-color: #f8f9fa;
    }
    .match-form h2 {
        font-size: 1.5rem;
        color: #28a745;
        margin-bottom: 15px;
    }
    .remove-btn {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    /* Style pour le bouton circulaire transparent avec contour vert */
    .circle-btn {
        width: 70px;
        height: 70px;
        border-radius: 50%;
        background-color: transparent;
        color: #28a745;
        border: 2px solid #28a745;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 2rem;
        box-shadow: 0px 4px 20px rgba(0, 0, 0, 0.1);
        transition: background-color 0.3s, color 0.3s;
    }

    .circle-btn:hover {
        background-color: #28a745;
        color: white;
    }
</style>

<!-- Add FontAwesome CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />

<div class="row justify-content-center">
    <div class="col-md-6">
        <div class="card">
            <div class="card-header text-center">
                <h1>Ajouter des matchs</h1>
            </div>
            <form action="add_match_action.php" method="post" id="matchForm">
                <div id="match-forms-container">
                    <!-- First Match Form (Non-removable) -->
                    <div class="match-form">
                        <h2>Match 1</h2>
                        <div class="form-group">
                            <label for="player1">Joueur 1</label>
                            <select class="form-control" id="player1" name="player1[]" required>
                                <?php
                                include 'db.php';
                                $result = $conn->query("SELECT * FROM players WHERE is_anonymized = 0");
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="player2">Joueur 2</label>
                            <select class="form-control" id="player2" name="player2[]" required>
                                <?php
                                $result->data_seek(0); // Remise à zéro du pointeur de résultats
                                while ($row = $result->fetch_assoc()) {
                                    echo "<option value='{$row['id']}'>{$row['name']}</option>";
                                }
                                ?>
                            </select>
                        </div>
                        <div class="form-group">
                            <label for="score1">Score Joueur 1</label>
                            <input type="number" class="form-control" id="score1" name="score1[]" required>
                        </div>
                        <div class="form-group">
                            <label for="score2">Score Joueur 2</label>
                            <input type="number" class="form-control" id="score2" name="score2[]" required>
                        </div>
                        <!-- Champ caché pour la date de modification -->
                        <input type="hidden" id="lastModified" name="lastModified[]" value="">
                    </div>
                </div>
                <!-- Bouton circulaire transparent pour ajouter un autre match -->
                <div class="d-flex align-items-center justify-content-center m-4">
                    <button type="button" class="circle-btn" id="add-match">
                        <i class="d-flex justify-content-center fas fa-plus"></i>
                    </button>
                </div>
                <div class="d-grid gap-2">
                    <button type="submit" class="btn btn-primary btn-block">Ajouter tous les matchs</button>
                    <a href="index.php" class="btn btn-secondary btn-block">Annuler</a>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let matchCounter = 1;

// Load saved matches from localStorage on page load
window.onload = function() {
    let savedMatches = JSON.parse(localStorage.getItem('matches'));
    if (savedMatches) {
        savedMatches.forEach((match, index) => {
            if (index > 0) { // Skip the first match as it's already in the DOM
                let newMatchForm = addNewMatchForm(index + 1);
                populateMatchForm(newMatchForm, match);
            } else {
                populateMatchForm(document.querySelector('.match-form'), match);
            }
        });
        matchCounter = savedMatches.length;
    }
};

document.getElementById('add-match').addEventListener('click', function() {
    matchCounter++;
    let newMatchForm = addNewMatchForm(matchCounter);
    saveMatchesToLocalStorage();
});

document.getElementById('matchForm').addEventListener('click', function(e) {
    if (e.target.closest('.remove-match-form')) {
        e.target.closest('.match-form').remove();
        matchCounter--;
        saveMatchesToLocalStorage();
    }
});

document.getElementById('matchForm').addEventListener('change', function() {
    saveMatchesToLocalStorage();
});

// Function to add a new match form
function addNewMatchForm(matchNumber, matchData = null) {
    let matchForm = document.querySelector('.match-form');
    let newMatchForm = matchForm.cloneNode(true);

    // Clear input values for the new form
    newMatchForm.querySelectorAll('input').forEach(input => input.value = '');
    newMatchForm.querySelectorAll('select').forEach(select => select.value = '');

    // Update the heading with the match number
    newMatchForm.querySelector('h2').textContent = `Match ${matchNumber}`;

    // Add remove button to the new form with FontAwesome icon
    let removeButton = document.createElement('button');
    removeButton.type = 'button';
    removeButton.className = 'btn btn-danger btn-sm remove-match-form';
    removeButton.innerHTML = '<i class="fas fa-minus"></i> Supprimer';
    let removeBtnDiv = newMatchForm.querySelector('.remove-btn');

    if (!removeBtnDiv) {
        removeBtnDiv = document.createElement('div');
        removeBtnDiv.className = 'remove-btn';
        newMatchForm.appendChild(removeBtnDiv);
    }

    removeBtnDiv.appendChild(removeButton);

    document.getElementById('match-forms-container').appendChild(newMatchForm);

    if (matchData) {
        populateMatchForm(newMatchForm, matchData);
    }

    return newMatchForm;
}

// Function to populate a match form with data
function populateMatchForm(matchForm, matchData) {
    matchForm.querySelector('[name="player1[]"]').value = matchData.player1;
    matchForm.querySelector('[name="player2[]"]').value = matchData.player2;
    matchForm.querySelector('[name="score1[]"]').value = matchData.score1;
    matchForm.querySelector('[name="score2[]"]').value = matchData.score2;

    // If there's a last modified time, display it
    if (matchData.lastModified) {
        let lastModifiedInput = matchForm.querySelector('input[name="lastModified[]"]');
        if (lastModifiedInput) {
            lastModifiedInput.value = matchData.lastModified;
        }

        let lastModifiedDiv = matchForm.querySelector('.last-modified');
        if (!lastModifiedDiv) {
            lastModifiedDiv = document.createElement('div');
            lastModifiedDiv.className = 'last-modified';
            matchForm.appendChild(lastModifiedDiv);
        }
        lastModifiedDiv.textContent = `Dernière modification : ${new Date(matchData.lastModified).toLocaleString()}`;
    }
}

// Function to save matches to localStorage with modification date
function saveMatchesToLocalStorage() {
    let matches = [];
    document.querySelectorAll('.match-form').forEach((matchForm, index) => {
        let matchData = {
            player1: matchForm.querySelector('[name="player1[]"]').value,
            player2: matchForm.querySelector('[name="player2[]"]').value,
            score1: matchForm.querySelector('[name="score1[]"]').value,
            score2: matchForm.querySelector('[name="score2[]"]').value,
            lastModified: matchForm.querySelector('input[name="lastModified[]"]').value || formatDateToMySQL(new Date()) // Use existing date or current date/time
        };
        matches.push(matchData);

        // Update the hidden input field with the modification date
        let lastModifiedInput = matchForm.querySelector('input[name="lastModified[]"]');
        if (lastModifiedInput) {
            lastModifiedInput.value = matchData.lastModified;
        }

        // Display the last modification date in the form
        let lastModifiedDiv = matchForm.querySelector('.last-modified');
        if (!lastModifiedDiv) {
            lastModifiedDiv = document.createElement('div');
            lastModifiedDiv.className = 'last-modified';
            matchForm.appendChild(lastModifiedDiv);
        }
        lastModifiedDiv.textContent = `Dernière modification : ${new Date(matchData.lastModified).toLocaleString()}`;
    });
    localStorage.setItem('matches', JSON.stringify(matches));
}

// Function to format date to MySQL format
function formatDateToMySQL(date) {
    let d = new Date(date);
    let year = d.getFullYear();
    let month = ('0' + (d.getMonth() + 1)).slice(-2);
    let day = ('0' + d.getDate()).slice(-2);
    let hours = ('0' + d.getHours()).slice(-2);
    let minutes = ('0' + d.getMinutes()).slice(-2);
    let seconds = ('0' + d.getSeconds()).slice(-2);

    return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}`;
}


</script>

<?php include('footer.php'); ?>
