# cv05 – Twig: šablóny a formuláre

*(English version below)*

Teória, príklad krok za krokom a súbory na stiahnutie sú na stránke predmetu: [Týždeň 6](https://beata.stehlikova.website.tuke.sk/it/06.html). Vzorová aplikácia beží na <https://sigma.tuke.sk/beata.stehlikova/it/ukazky/cv05/>.

Čo dnes urobíš: HTML stránok presunieš z `views/*.php` do šablón `views/*.twig`, ktoré vykresľuje knižnica Twig (stiahne ju Composer), a pridáš formulár na nový záznam hlavnej entity: route `POST`, metódy `create()` a `store()`, `insert()` v modeli, presmerovanie na detail.

## Úlohy

**Úloha 6.1: Repository cv05 z cv04, twig.zip a Composer.** Prijmi zadanie *cv05* ([link](https://classroom50.org/tuke-fberg-stehlikova/informacne-technologie-5k/assignments/cv05/accept?k=xzmai3oh)) a repository naklonuj do `~/web/it/cv05` podľa návodu [04 Odovzdávanie](https://beata.stehlikova.website.tuke.sk/navody/04-odovzdavanie.html), časti 2 a 3 (posledné slovo príkazu je `cv05`). Potom v termináli v Kubeflow:

```bash
cd ~/web/it
cp cv04/config.php cv04/config.example.php cv04/.gitignore cv04/.htaccess cv04/composer.json cv04/index.php cv05/
cp -r cv04/sql cv04/src cv04/views cv05/
cd cv05
curl -O https://beata.stehlikova.website.tuke.sk/it/twig.zip
unzip -o twig.zip && rm twig.zip
composer require twig/twig
```

Čo sa stalo: z `cv04` sú v `cv05` všetky súbory okrem `.git/`, `vendor/` a `README.md`. `unzip -o` prepísal `src/helpers.php`, `src/Router.php`, `src/Http/Request.php`, `src/Http/Response.php` a pridal do `views/` sedem súborov `.twig` (staré `.php` tam zatiaľ ostali). `composer require` stiahol Twig a vyrobil `vendor/` a `composer.lock`; výpis a kontrola sú na stránke predmetu v časti „Composer v Kubeflow krok za krokom“, kroky 3 a 4.

Pravý klik na priečinok `cv05` → **Upload Folder**. Kontrola: `https://sigma.tuke.sk/student/meno.priezvisko/it/cv05/` ukáže úvodnú stránku (zatiaľ jednu vetu; menu má Úvod, Kontrola, Debug, O aplikácii), `…/cv05/nic` ukáže stránku „Nenájdené“. Kontrola a Debug zatiaľ hlásia chybu – opravíš to v úlohe 6.2. Commit a push: `cv05: Twig`.

**Úloha 6.2: Všetky stránky cez šablóny.**

1. Vo `views/layout.twig` pridaj do menu odkaz na každý svoj číselník a na hlavnú entitu: `<a href="{{ url('/categories') }}">Kategórie</a>`. Kontrola: každý číselník sa zobrazí ako tabuľka; jeho controller si nemenil.
2. V `src/Controller/HomeController.php` prepíš `kontrola()` a `debug()` podľa `HomeController` na stránke predmetu (časť „Kontrola a debug“); v `kontrola()` daj do poľa svoje modely a pridaj k riadkom `use` svoje modely, `App\Database` a `PDO`. Kontrola: `…/cv05/kontrola` ukáže tabuľky a modely, `…/cv05/debug?q=abc` ukáže sedem riadkov.
3. Obsah svojho `views/home.php` prepíš do `views/home.twig` a `views/about.php` do nového `views/about.twig`: PHP značky preč, `<?= url('/x') ?>` → `{{ url('/x') }}`, `<?= e($x) ?>` → `{{ x }}`; na začiatok `{% extends "layout.twig" %}` a obsah do `{% block content %} … {% endblock %}`. Kontrola: úvod aj `/o-aplikacii` fungujú.
4. Zmaž všetky `.php` súbory vo `views/` (v Explorer pravý klik → **Delete**). Kontrola: preklikaj celé menu; nič sa nezmenilo.

**Úloha 6.3: insert() v modeli hlavnej entity.** Do svojho modelu hlavnej entity pridaj metódu `insert(array $data): int` podľa pojmu „insert() a lastInsertId()“ na stránke predmetu: v `INSERT` sú stĺpce tvojej tabuľky, ktoré vypĺňa človek (bez `id` a bez stĺpcov s `DEFAULT`), každý ako parameter `:nazov`. Kontrola zatiaľ nie je možná – metódu zavolá controller v úlohe 6.4.

**Úloha 6.4: create() a store() v controlleri hlavnej entity.** Do controllera hlavnej entity pridaj metódy `create()` a `store()` podľa `RecipeController` na stránke predmetu (časť „Ako to funguje“): v `store()` čítaj cez `post()` polia svojej tabuľky, čísla preveď `(int)`, kontroluj prázdny názov; pre `<select>` použi model svojho číselníka pripojeného cez 1:N. Do `index()` pridaj kľúč `'create' => '/tvoja-entita/new'`. V `index.php` pridaj dve route: `$router->get('/tvoja-entita/new', [TvojController::class, 'create']);` a `$router->post('/tvoja-entita', [TvojController::class, 'store']);`. Kontrola: nad zoznamom hlavnej entity je odkaz *Pridať záznam*; `…/cv05/tvoja-entita/new` zatiaľ hlási `Unable to find template "form.twig"` – to je správne, formulár je úloha 6.5.

**Úloha 6.5: form.twig.** Vytvor `views/form.twig` podľa pojmu „Formulár“ na stránke predmetu: `action` na adresu svojej POST route, jedno pole pre každý stĺpec z `insert()` s `name` rovnakým ako stĺpec, `<select>` pre cudzí kľúč so záznamami číselníka, `required` pri názve. Kontrola: klik na *Pridať záznam* ukáže formulár; po vyplnení a kliknutí na *Uložiť* sa zobrazí detail nového záznamu a v adrese je jeho `id`; v karte Network (`F12` → Network) sú pod sebou požiadavka s metódou `POST` a stavom `302` a požiadavka `GET` so stavom `200`; `F5` na detaile nepridá ďalší záznam; nový záznam je v zozname a v Adminer v tabuľke. Potom v Chrome pravý klik na pole názvu → **Inspect**, v značke `<input>` dvojklik na `required` a zmaž ho, odošli prázdny formulár: zobrazí sa formulár s hláškou a ostatné polia majú tvoje hodnoty.

**Úloha 6.6: Vlastná stránka.** Vymysli jednu stránku, ktorú tvoja aplikácia zatiaľ nemá, a urob ju štyrmi krokmi z časti „Nová stránka v aplikácii: postup“ na stránke predmetu. Námety: tri najnovšie záznamy; záznamy hlavnej entity s jednou hodnotou číselníka (napríklad len ľahké recepty – nová metóda modelu s `WHERE`); náhodný záznam; prehľad, koľko záznamov má každá hodnota číselníka. Podmienky: nová metóda controllera, nová route, nová šablóna s `{% for %}`, `{% if %}` a aspoň jedným filtrom, odkaz v menu. Názov stránky a čo ukazuje napíš do tohto `README.md`. Kontrola: stránka funguje z menu aj z adresy; pri obhajobe ju vysvetlíš.

**Úloha 6.7: Odovzdanie.** Commit a push podľa návodu 04 Odovzdávanie, časť 5. Na GitHub skontroluj, že je tam `composer.json` s `twig/twig`, `composer.lock`, `views/` len so súbormi `.twig` (aj `form.twig`), tvoj controller hlavnej entity s `create()` a `store()`, tvoj model s `insert()`, tvoja vlastná stránka. `config.php` a `vendor/` tam nie sú.

**Úloha 6.8: Kontrola na sigma.tuke.sk.** Pravý klik na `cv05` → **Upload Folder**. Preklikaj menu: úvod, každý číselník, hlavná entita, detail, vyhľadávanie, pridanie záznamu, vlastná stránka, kontrola, debug, o aplikácii. Over, že `…/cv05/nic` ukáže stránku „Nenájdené“, `…/cv05/vendor/autoload.php` vráti 404 a `…/cv05/config.php` vráti 403. Do tohto `README.md` doplň tabuľku všetkých route:

| HTTP metóda a relatívna adresa | Controller a metóda | Čo zobrazí alebo urobí |
|---|---|---|
| GET `/` | `HomeController::index` | úvodná stránka |
| … | … | … |

**Hodnotenie:** 1 bod. Aplikácia vo webovom priestore na sigma.tuke.sk má všetky stránky zo šablón Twig, zoznam hlavnej entity má odkaz na formulár, formulár uloží nový záznam a presmeruje na jeho detail, aplikácia má jednu vlastnú stránku navyše. Commit a push najneskôr do nasledujúceho cvičenia.

---

# cv05 – Twig: templates and forms

Theory, a step-by-step example and the files to download are on the course page: [Week 6](https://beata.stehlikova.website.tuke.sk/it/06.html). The sample application runs at <https://sigma.tuke.sk/beata.stehlikova/it/ukazky/cv05/>.

What you do today: you move the HTML of the pages from `views/*.php` into templates `views/*.twig`, rendered by the Twig library (Composer downloads it), and you add a form for a new record of the main entity: a `POST` route, the methods `create()` and `store()`, `insert()` in the model, a redirect to the detail.

## Tasks

**Task 6.1: Repository cv05 from cv04, twig.zip and Composer.** Accept the assignment *cv05* ([link](https://classroom50.org/tuke-fberg-stehlikova/informacne-technologie-5k/assignments/cv05/accept?k=xzmai3oh)) and clone the repository into `~/web/it/cv05` following the guide [04 Submitting](https://beata.stehlikova.website.tuke.sk/navody/04-odovzdavanie.html), parts 2 and 3 (the last word of the command is `cv05`). Then in the terminal in Kubeflow:

```bash
cd ~/web/it
cp cv04/config.php cv04/config.example.php cv04/.gitignore cv04/.htaccess cv04/composer.json cv04/index.php cv05/
cp -r cv04/sql cv04/src cv04/views cv05/
cd cv05
curl -O https://beata.stehlikova.website.tuke.sk/it/twig.zip
unzip -o twig.zip && rm twig.zip
composer require twig/twig
```

What happened: all files from `cv04` are in `cv05` except `.git/`, `vendor/` and `README.md`. `unzip -o` replaced `src/helpers.php`, `src/Router.php`, `src/Http/Request.php`, `src/Http/Response.php` and added seven `.twig` files to `views/` (the old `.php` files stayed there for now). `composer require` downloaded Twig and made `vendor/` and `composer.lock`; the output and the check are on the course page in the part “Composer in Kubeflow step by step”, steps 3 and 4.

Right-click the folder `cv05` → **Upload Folder**. Check: `https://sigma.tuke.sk/student/meno.priezvisko/it/cv05/` shows the home page (one sentence for now; the menu has Úvod, Kontrola, Debug, O aplikácii), `…/cv05/nic` shows the “Not found” page. Kontrola and Debug report an error for now – you fix that in task 6.2. Commit and push: `cv05: Twig`.

**Task 6.2: All pages through templates.**

1. In `views/layout.twig` add a menu link to each of your lookup tables and to the main entity: `<a href="{{ url('/categories') }}">Kategórie</a>`. Check: every lookup table shows as a table; you did not change its controller.
2. In `src/Controller/HomeController.php` rewrite `kontrola()` and `debug()` following `HomeController` on the course page (part “Check and debug”); in `kontrola()` put your models into the array and add your models, `App\Database` and `PDO` to the `use` lines. Check: `…/cv05/kontrola` shows the tables and models, `…/cv05/debug?q=abc` shows seven rows.
3. Rewrite the content of your `views/home.php` into `views/home.twig` and `views/about.php` into a new `views/about.twig`: PHP tags out, `<?= url('/x') ?>` → `{{ url('/x') }}`, `<?= e($x) ?>` → `{{ x }}`; `{% extends "layout.twig" %}` at the beginning and the content into `{% block content %} … {% endblock %}`. Check: the home page and `/o-aplikacii` work.
4. Delete all `.php` files in `views/` (in the Explorer right-click → **Delete**). Check: click through the whole menu; nothing changed.

**Task 6.3: insert() in the main entity model.** Add the method `insert(array $data): int` to your main entity model following the term “insert() and lastInsertId()” on the course page: in the `INSERT` are the columns of your table that a person fills in (without `id` and without columns with `DEFAULT`), each as a parameter `:name`. A check is not possible yet – the controller calls the method in task 6.4.

**Task 6.4: create() and store() in the main entity controller.** Add the methods `create()` and `store()` to the main entity controller following `RecipeController` on the course page (part “How it works”): in `store()` read the fields of your table through `post()`, convert numbers with `(int)`, check the empty title; for the `<select>` use the model of your lookup table connected through 1:N. Add the key `'create' => '/your-entity/new'` to `index()`. In `index.php` add two routes: `$router->get('/your-entity/new', [YourController::class, 'create']);` and `$router->post('/your-entity', [YourController::class, 'store']);`. Check: above the main entity list there is the link *Pridať záznam*; `…/cv05/your-entity/new` reports `Unable to find template "form.twig"` for now – that is correct, the form is task 6.5.

**Task 6.5: form.twig.** Create `views/form.twig` following the term “Form” on the course page: `action` to the address of your POST route, one field for every column from `insert()` with `name` the same as the column, a `<select>` for the foreign key with the lookup table records, `required` on the title. Check: a click on *Pridať záznam* shows the form; after filling it in and clicking *Uložiť* the detail of the new record shows and its `id` is in the address; in the Network tab (`F12` → Network) there are, one under the other, a request with the method `POST` and the status `302` and a `GET` request with the status `200`; `F5` on the detail does not add another record; the new record is in the list and in Adminer in the table. Then in Chrome right-click the title field → **Inspect**, double-click `required` in the `<input>` tag and delete it, submit the empty form: the form shows with the message and the other fields have your values.

**Task 6.6: Your own page.** Think up one page your application does not have yet and make it with the four steps from the part “A new page in the application: the procedure” on the course page. Ideas: the three newest records; records of the main entity with one lookup table value (for example only easy recipes – a new model method with `WHERE`); a random record; an overview of how many records each lookup table value has. Conditions: a new controller method, a new route, a new template with `{% for %}`, `{% if %}` and at least one filter, a link in the menu. Write the name of the page and what it shows into this `README.md`. Check: the page works from the menu and from the address; you will explain it at the defence.

**Task 6.7: Submission.** Commit and push following the guide 04 Submitting, part 5. On GitHub check that there is `composer.json` with `twig/twig`, `composer.lock`, `views/` with `.twig` files only (`form.twig` too), your main entity controller with `create()` and `store()`, your model with `insert()`, your own page. `config.php` and `vendor/` are not there.

**Task 6.8: Check on sigma.tuke.sk.** Right-click `cv05` → **Upload Folder**. Click through the menu: home, every lookup table, main entity, detail, search, adding a record, your own page, check, debug, about. Verify that `…/cv05/nic` shows the “Not found” page, `…/cv05/vendor/autoload.php` returns 404 and `…/cv05/config.php` returns 403. Add a table of all routes to this `README.md`:

| HTTP method and relative address | Controller and method | What it shows or does |
|---|---|---|
| GET `/` | `HomeController::index` | home page |
| … | … | … |

**Grading:** 1 point. The application in your web space on sigma.tuke.sk has all pages from Twig templates, the main entity list has a link to the form, the form saves a new record and redirects to its detail, the application has one page of its own in addition. Commit and push by the next lab at the latest.
