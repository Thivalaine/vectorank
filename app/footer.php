<!-- footer.php -->
<footer class="footer">
    <div class="container text-center">
        <p>&copy; 2024 Vectorank. Tous droits réservés.</p>
    </div>
</footer>

<style>
    .footer {
        padding: 20px 0; /* Espacement vertical */
        flex: 0 1 auto; /* Le footer ne grandit pas, mais peut se réduire */
        width: 100%; /* Largeur complète */
        margin-top: 20px;
    }

    .footer .container {
        display: flex;
        flex-direction: column; /* Alignement vertical */
        align-items: center; /* Centrer le contenu */
    }

    .footer p {
        margin: 0; /* Supprime la marge */
    }
</style>

<!-- Ajoute ici les scripts communs -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jquery/3.6.0/jquery.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/select2/4.0.13/js/select2.min.js"></script>
    <script>
        $(document).ready(function() {
            $('.select2').select2({
                placeholder: 'Sélectionnez des participants',
                allowClear: true,
                width: '100%',
            });
        });
    </script>
</body>
</html>
