<div class="col-lg-8 col-md-12">
    <nav class="navbar navbar-expand-lg navigation-2 navigation">
        <a class="navbar-brand text-uppercase d-lg-none" href="./">
            <img src="images/logo.png" alt="" class="img-fluid">
        </a>
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbar-collapse"
                aria-controls="navbar-collapse" aria-expanded="false" aria-label="Toggle navigation">
            <span class="ti-menu"></span>
        </button>

        <div class="collapse navbar-collapse" id="navbar-collapse">
            <ul id="menu" class="menu navbar-nav mx-auto">
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle" href="./" id="navbarDropdown""
                    aria-haspopup="true" aria-expanded="false">
                    Home
                    </a>
                </li>

                {% for category in categories %}

                <li class="nav-item"><a href=".?route=categorie&slug={{ category.getCategorySlug }}">{{ category.getCategoryName }}</a></li>
                {% endfor %}

                <li class="nav-item"><a href="./?inscription" class="nav-link">Inscription</a></li>
                <li class="nav-item"><a href="./?connect" class="nav-link">Connexion</a></li>
            </ul>
        </div>
    </nav>
</div>
