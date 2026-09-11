# QA Spec: SatisFactory Planner

## Doel
SatisFactory Planner is een browser-tool waarmee spelers van het spel
Satisfactory hun fabrieksplanning uitwerken: productielijnen ontwerpen,
stroomvoorziening berekenen en optioneel een gekoppelde dedicated
game-server beheren. Gebruikers registreren een account, maken "game
saves" aan en kunnen die delen/samen beheren met andere spelers via een
rollen-/permissiesysteem (bron: README.md, `private/controllers/GameSaves.php`,
`private/types/role.php`).

## Belangrijkste flows

### Registreren en inloggen
- Startpunt: `/register` (`private/views/pages/register.php`).
- Stappen: gebruiker vult username, email, password, password-confirmatie
  in; server valideert lengtes en tekens en checkt uniekheid van
  username/email (register.php:10-34); `Users::createUser()` hasht het
  wachtwoord en genereert een verificatiecode die per e-mail wordt
  verstuurd (`Users.php:75-81`).
- Gebruiker wordt doorgestuurd naar `/login/verify` en moet de link in de
  e-mail volgen voordat inloggen lukt — `AuthControler::login()` blokkeert
  ongeverifieerde accounts (`AuthControler.php:22-25`).
- Inloggen op `/login` met username/password; bij 5 mislukte pogingen
  binnen een uur voor dezelfde combinatie gebruiker+IP wordt verder
  inloggen geblokkeerd (`AuthControler.php:164-171`).
- Verwacht resultaat: geverifieerde, niet-geblokkeerde gebruiker komt op
  `/game_saves` terecht met een sessie (`$_SESSION['userId']`).
- Alternatieve route: inloggen/registreren via Google OAuth
  (`private/views/pages/login/google-oauth/index.php`), met koppeling aan
  een bestaand account op basis van e-mailadres of aanmaak van een nieuw
  account. <!-- ONZEKER: OAuth-inlogpad doorloopt geen login-attempt/lockout-tracking, onduidelijk of dat bewust is -->
- Wachtwoord vergeten: er is **geen** self-service "forgot password"-flow
  gevonden in de code; wachtwoord wijzigen kan alleen terwijl je al bent
  ingelogd via de accountpagina (`Users::handlePasswordUpdate()`,
  `Users.php:258-275`).

### Game save aanmaken en delen
- Startpunt: `/game_saves` (`private/views/pages/game_saves.php`).
- Stappen: gebruiker maakt een nieuwe save aan (`GameSaves::createSaveGame()`,
  `GameSaves.php:141-155`), wat de gebruiker automatisch als eigenaar
  (`role_id=1`) aan de save koppelt.
- Eigenaar kan andere gebruikers uitnodigen met een rol
  (`Role`: OWNER/ENGINEER/SUPERVISOR/FACTORY_WORKER) via
  `private/ajax/userPermissions.php`, wat een pending (`accepted=0`) rij
  aanmaakt; de uitgenodigde gebruiker accepteert of weigert
  (`GameSaves::acceptRequest/declineRequest`, `GameSaves.php:378-396`).
- Verwacht resultaat: geaccepteerde gebruikers krijgen toegang tot de save
  volgens hun rol/permissies, gecontroleerd via
  `GameSaves::checkAccess()` (`GameSaves.php:25-61`).

### Productielijn ontwerpen
- Startpunt: `/game_save/{id}` → `/game_save/{id}/production_line/{lineId}`
  (React/TS-editor onder `public/TypeScript/ProductionLines/`).
- Stappen: gebruiker voegt machines/recepten toe (`ProductionCard`-
  componenten), stelt clock speed en eventueel dubbele output in, koppelt
  stroomverbruik, en kan een checklist en visualisatie (Cytoscape-graaf)
  bekijken.
- Opslaan gebeurt via `private/ajax/saveProductionLine.php` →
  `ProductionLines::saveProductionLine()`
  (`ProductionLines.php:144-261`), wat transactioneel input/output/power/
  import-source-rijen herschrijft.
- Verwacht resultaat: de productielijn en de bijbehorende berekeningen
  (output, benodigde input, stroom) zijn opgeslagen en direct zichtbaar in
  de editor en visualisatie.
- Sub-flow "cross-line imports": een lijn kan grondstoffen betrekken uit de
  export van een andere actieve productielijn binnen dezelfde save
  (`ProductionLines::getImportSourceCandidates()`,
  `ProductionLines.php:57-85`, en `ImportsCard`-componenten). Dit is een
  recent toegevoegde feature (changelog 1.17.0-1.17.5) met meerdere
  snelle bugfixes erna. <!-- ONZEKER: gezien de reeks recente fixes is onduidelijk of alle edge cases (bv. gelijktijdig bewerken van beide lijnen) al stabiel zijn -->

### Stroomvoorziening beheren
- Startpunt: stroomtabblad/-modal binnen een game save
  (`public/TypeScript/PowerProduction/`).
- Stappen: gebruiker voegt stroomgebouwen toe met aantal en clock speed
  (`private/ajax/powerProduction/add.php` e.a.); berekening gebeurt
  server-side in `private/ajax/powerProduction/calculate.php`, inclusief
  een bonus voor (Boosted) Alien Power Augmenters.
- Verwacht resultaat: totale stroomproductie van de save wordt bijgewerkt
  (`GameSaves::updatePowerProduction()`) en getoond aan de gebruiker.

### Dedicated server koppelen en beheren
- Startpunt: `/game_save/{id}/dedicated_server`.
- Stappen: gebruiker met voldoende rechten (`Permission::SERVER_MANAGE`
  voor koppelen/beheren, `SERVER_VIEW` voor alleen bekijken) voert IP,
  poort en API-token van de eigen dedicated server in; token wordt
  versleuteld opgeslagen (`DedicatedServer::saveServer()`,
  `DedicatedServer.php:12-61`).
- Vervolgens kan de gebruiker de serverstatus opvragen, sessies bekijken,
  saves downloaden of de server afsluiten via
  `private/ajax/dedicatedServerAPI/*.php`, die elk opnieuw
  `GameSaves::checkAccess()` met de juiste permissie afdwingen.
- Verwacht resultaat: live serverstatus/dashboard in de UI
  (`public/TypeScript/DedicatedServerDashboard/`).
  <!-- ONZEKER: enumerateSessions.php is niet volledig doorgelicht door het onderzoek; het daadwerkelijke sessie-ophaalgedrag na de toegangscheck is niet bevestigd -->

### Adminbeheer
- Startpunt: `/admin` (alleen zichtbaar/bereikbaar met
  `$_SESSION['admin']`, gezet bij inloggen als `users.admin = 1`).
- Functies: gebruikersbeheer (lijst, bewerken, verwijderen, save-inzage),
  error-logs (404/403) met filters, login-attempts-rapportage, site-
  instellingen (maintenance-mode, toegestane IP's) — alleen zichtbaar voor
  de site-eigenaar (`SiteSettings::isOwner()`), en het versturen van een
  update-e-mail naar geverifieerde gebruikers.
- Verwacht resultaat: admin kan gebruikers en site-status beheren zonder
  de gewone gebruikersflows te raken.

## Bekende beperkingen
- Er is geen self-service "wachtwoord vergeten"-flow: alleen wachtwoord
  wijzigen terwijl ingelogd is mogelijk (`Users::handlePasswordUpdate()`,
  `Users.php:258-275`); geen enkele route/bestand met reset-token-logica
  gevonden.
- De zoekfunctie op de admin-pagina "Login Attempts" is expliciet als
  onaf gemarkeerd: `//TODO: implement the search functionality`
  (`private/views/pages/admin/loginAttempts.php:109`), terwijl de
  onderliggende ajax-endpoint en controller-methode wel functioneel zijn.
  Dit is een bewust gemarkeerde onvolledige koppeling, geen gok.
- Twee routes zijn met een expliciete TODO gemarkeerd als tijdelijk:
  `POST /game_save/{id}` en
  `POST /game_save/{id}/production_line/{lineId}` dragen beide
  `// TODO: CHANGE TO API` (`private/routes.php:10` en `:28`) — dit zijn
  bewust-tijdelijke implementaties volgens de code zelf.
- Google OAuth-verzoeken (token-exchange en userinfo-call) draaien met
  `CURLOPT_SSL_VERIFYPEER` uitgeschakeld
  (`private/views/pages/login/google-oauth/index.php:65,86`). Dit staat
  niet als TODO in de code, maar is een expliciete, code-zichtbare
  instelling en daarom hier vermeld als bestaand (niet per se bewust
  gewenst) gedrag.
- `private/views/pages/game_save/dedicated_server.php:332` bevat de
  comment `<!--todo: move to TS-->`, wat aangeeft dat een deel van deze
  pagina bewust nog niet gemigreerd is naar de TypeScript-module.

## Te bevestigen door maintainer
- Is het uitschakelen van SSL-verificatie bij de Google OAuth-calls
  (`google-oauth/index.php:65,86`) een bewuste keuze (bv. voor een
  specifieke lokale/test-omgeving) of een oversight die gefixt moet
  worden? Dit raakt de veiligheid van het hele OAuth-inlogpad.
- Is het ontbreken van een "wachtwoord vergeten"-flow een bewust
  productbesluit, of een ontbrekende feature die nog gebouwd moet worden?
- `Permission::SERVER_DEPLOY` (`private/types/permission.php:13`) is
  gedefinieerd maar wordt nergens gebruikt — is dit een geplande, nog niet
  gebouwde permissie, of dode code die verwijderd kan worden?
- Changelog 1.16.0 meldt dat auto-import/export/power/save-instellingen
  zijn "verwijderd om configuratie te vereenvoudigen", maar
  `ProductionLineSettingsModal.tsx` en
  `private/ajax/updateProductionLineSettings.php`
  (`ProductionLineSettings::updateProductionLineSettings`) bestaan en
  bewaren nog steeds `auto_import_export`/`auto_power_machine`/
  `auto_save`-velden. Is deze modal/instelling nog bereikbaar in de UI, en
  zo ja, is dat gewenst of restant-functionaliteit die verwijderd had
  moeten worden?
- Login via Google OAuth doorloopt geen login-attempt-/lockout-registratie
  (in tegenstelling tot het reguliere wachtwoord-inlogpad). Is dat een
  bewust ontwerp (OAuth heeft immers geen wachtwoord om te brute-forcen)
  of een gat in de rate-limiting?
- De basis-databaseschema (users, game_saves, roles, permissions, items,
  recipes, buildings, enz.) staat niet in `database/migrations/` — alleen
  één incrementele patch (`2026_07_20_create_production_line_import_sources.sql`)
  is aanwezig. Hoe wordt een nieuwe/lokale omgeving momenteel van een
  werkende database voorzien, en is dat proces relevant voor een
  geautomatiseerde audit?
- Is de onvolkomen zoekfunctie op de "Login Attempts"-adminpagina
  (zie Bekende beperkingen) gepland werk voor een volgende sprint, of kan
  de audit dit als bug behandelen?

## Buiten scope
- E-mailverzending (SMTP via PHPMailer, `private/config/settings.php`,
  `private/controllers/Mailer.php`) — verificatie-mails en admin
  update-mails; het daadwerkelijk aankomen/renderen van e-mails valt
  buiten geautomatiseerde UI-audit.
- Google OAuth als externe identiteitsprovider (client id/secret via
  `.env`) — het gedrag van Google's eigen inlogscherm is geen onderdeel
  van deze applicatie.
- De dedicated-server-API zelf (het spel-servergedrag achter
  `private/controllers/APIClient.php`): de planner praat er alleen mee via
  HTTP-calls; het gedrag van de game-server zelf is een externe
  integratie.
- Databaseverbinding (MySQL, credentials in `private/config/settings.php`)
  en de admin-API-token (`ADMIN_API_KEY` uit `.env`,
  `AuthControler::validateAdminToken()`), als infrastructuur-/
  configuratiezaken.

## Testdata
Er is geen seeder, migratie-runner of testdata-script in de repository
gevonden die een `migrate:fresh --seed`-achtige stap uitvoert. Het enige
migratiebestand (`database/migrations/2026_07_20_create_production_line_import_sources.sql`)
voegt alleen de tabel `production_line_import_sources` toe aan een reeds
bestaand schema; er is geen bestand dat de basistabellen (users,
game_saves, roles, permissions, items, recipes, buildings, enz.) aanmaakt
of een standaard adminaccount/demodata seedt (`private/controllers/Database.php`
en `NewDatabase.php` bevatten alleen generieke query-helpers, geen
`CREATE TABLE`- of seed-statements). <!-- ONZEKER: een fresh checkout van deze repo kan zichzelf dus niet bootstrappen tot een werkende database; hoe dit in productie/ontwikkeling wél gebeurt is niet uit de code af te leiden -->

---

## Prioriteiten voor de maintainer (top-punten om als eerste na te lopen)
1. Google OAuth-calls draaien met SSL-verificatie uitgeschakeld
   (`login/google-oauth/index.php:65,86`) — mogelijk beveiligingsrisico,
   hoogste prioriteit om te bevestigen.
2. Ontbrekende "wachtwoord vergeten"-flow — bewust of gat?
3. Onduidelijke status van `ProductionLineSettingsModal`/auto-instellingen
   na de "verwijderd"-melding in changelog 1.16.0.
4. Onvolledige zoekfunctie op de admin "Login Attempts"-pagina — geplande
   afronding of acceptabel als bug?
5. Ontbrekend basis-schema/seeder in `database/migrations/` — relevant
   voor hoe de audit-omgeving zelf wordt opgezet.
6. Onbenutte `Permission::SERVER_DEPLOY` — geplande feature of dode code?
7. Geen login-attempt/lockout-tracking op het Google OAuth-pad — bewust
   verschil met het wachtwoord-pad of hiaat?
