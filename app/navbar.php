<nav class="navbar navbar-expand-lg bg-body-tertiary">
  <div class="container">
    <a class="navbar-brand" href="index.php">Vectorank <i class="fa-solid fa-table-tennis-paddle-ball"></i></a>
    <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon"></span>
    </button>
    <div class="collapse navbar-collapse" id="navbarSupportedContent">
      <ul class="navbar-nav me-auto mb-lg-0">
        <li class="nav-item dropdown">
            <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="fas fa-gamepad"></i> Matchs
            </a>
            <ul class="dropdown-menu">
                <li><a class="dropdown-item" href="matches.php"><i class="fas fa-list"></i> Liste des matchs</a></li>
                <li><a class="dropdown-item" href="add_match.php"><i class="fas fa-plus-circle"></i> Ajouter un match</a></li>
            </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-users"></i> Joueurs
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="index.php"><i class="fas fa-list"></i> Liste des joueurs</a></li>
            <li><a class="dropdown-item" href="add_player.php"><i class="fas fa-user-plus"></i> Ajouter un joueur</a></li>
          </ul>
        </li>
        <li class="nav-item dropdown">
          <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-trophy"></i> Tournois
          </a>
          <ul class="dropdown-menu">
            <li><a class="dropdown-item" href="tournament_list.php"><i class="fas fa-list"></i> Liste des tournois</a></li>
            <li><a class="dropdown-item" href="tournaments.php"><i class="fas fa-plus-circle"></i> Créer un tournoi</a></li>
          </ul>
        </li>
      </ul>
      <ul class="navbar-nav ms-auto">
        <li class="nav-item">
          <a class="nav-link" href="#"><i class="fas fa-user-circle"></i> Mon Compte</a>
        </li>
      </ul>
    </div>
  </div>
</nav>
<style>
  .container {
    background-color: #ffffff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
  }
</style>