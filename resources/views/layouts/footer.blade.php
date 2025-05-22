<!-- ===== FOOTER =================================================== -->
<footer class="bg-dark text-white py-4 mt-4 mt-auto">
    <div class="container">
        <div class="row gy-4">
            <!-- Logo i opis -->
            <div class="col-md-4">
                <h3 class="fw-bold mb-4">Platforma Nowoczesnej Edukacji</h3>
                <p class="text-light opacity-75 mb-4">Akredytowany Niepubliczny Ośrodek Doskonalenia Nauczycieli. Skuteczne szkolenia i wsparcie dla nauczycieli oraz kadry zarządzającej.</p>
                <div class="d-flex gap-3 fs-4">
                    <a href="https://www.facebook.com/WaldemarGrabowskiEdukacja/" target="_blank" class="text-light opacity-75 hover-lift"><i class="bi bi-facebook"></i></a>
                    <a href="https://www.instagram.com/platforma.nowoczesnej.edukacji/" target="_blank" class="text-light opacity-75 hover-lift"><i class="bi bi-instagram"></i></a>
                    <a href="https://www.youtube.com/c/WaldemarGrabowskiEdukacja?sub_confirmation=1" target="_blank" class="text-light opacity-75 hover-lift"><i class="bi bi-youtube"></i></a>
                    <a href="https://www.linkedin.com/in/waldemar-grabowski/" target="_blank" class="text-light opacity-75 hover-lift"><i class="bi bi-linkedin"></i></a>
                </div>
            </div>
            
            <!-- Nawigacja -->
            <div class="col-md-2 col-6">
                <h5 class="text-uppercase fw-bold mb-4">Nawigacja</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">Strona główna</a></li>
                    <li class="mb-2"><a href="#courses" class="text-light opacity-75 text-decoration-none hover-lift">Oferta szkoleń</a></li>
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">O nas</a></li>
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">Blog</a></li>
                    <li class="mb-2"><a href="#kontakt" class="text-light opacity-75 text-decoration-none hover-lift">Kontakt</a></li>
                </ul>
            </div>
            
            <!-- Szkolenia -->
            <div class="col-md-2 col-6">
                <h5 class="text-uppercase fw-bold mb-4">Szkolenia</h5>
                <ul class="list-unstyled mb-0">
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">Dla nauczycieli</a></li>
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">Dla dyrektorów</a></li>
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">Rady pedagogiczne</a></li>
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">Webinary</a></li>
                    <li class="mb-2"><a href="#" class="text-light opacity-75 text-decoration-none hover-lift">Kursy online</a></li>
                </ul>
            </div>
            
            <!-- Kontakt -->
            <div class="col-md-4">
                <h5 class="text-uppercase fw-bold mb-4">Kontakt</h5>
                <ul class="list-unstyled mb-4">
                    <li class="mb-3 d-flex">
                        <i class="bi bi-telephone-fill me-2 text-primary"></i>
                        <span>+48 501 654 274</span>
                    </li>
                    <li class="mb-3 d-flex">
                        <i class="bi bi-envelope-fill me-2 text-primary"></i>
                        <span>kontakt@nowoczesna-edukacja.pl</span>
                    </li>
                    <li class="mb-3 d-flex">
                        <i class="bi bi-geo-alt-fill me-2 text-primary"></i>
                        <span>ul. A. Zamoyskiego 30/14, 09-320 Bieżuń</span>
                    </li>
                </ul>
            </div>
        </div>
        
        <hr class="border-secondary my-4">
        
        <div class="d-flex flex-column flex-md-row justify-content-between align-items-center small">
            <p class="mb-2 mb-md-0 text-light opacity-75">&copy; {{ date('Y') }} Platforma Nowoczesnej Edukacji. Wszelkie prawa zastrzeżone.</p>
            <ul class="list-inline mb-0">
                <li class="list-inline-item"><a href="{{ route('polityka-prywatnosci') }}" class="text-light opacity-75 text-decoration-none hover-lift">Polityka prywatności</a></li>
                <li class="list-inline-item"><a href="{{ route('regulamin') }}" class="text-light opacity-75 text-decoration-none hover-lift">Regulamin</a></li>
                <li class="list-inline-item"><a href="{{ route('rodo') }}" class="text-light opacity-75 text-decoration-none hover-lift">RODO</a></li>
            </ul>
        </div>
    </div>
</footer>