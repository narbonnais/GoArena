# Demarrage de partie avec ID (guide junior)

## Contexte (ce qui se passe aujourd hui)
- La page `/go` lance la partie en visitant `/go/play?boardSize=...&botId=...&timeControl=...`.
- La route `/go/play` rend directement `go/Play` avec ces query params.
- Du coup, si tu fais "back", tu retombes sur `/go/play?...` et la partie se relance.
- Les parties contre bot n ont pas d ID tant qu on ne sauvegarde pas (fin ou exit).

## Objectif
- La partie demarre uniquement apres clic sur un bouton + confirmation.
- Chaque partie (meme vs bot) a un ID des le demarrage.
- Le "back" navigateur ne relance pas une nouvelle partie.

## Plan simple (a retenir)
1. Creer un endpoint qui *cree* une partie en base et renvoie un `game_id`.
2. Depuis `/go`, appeler cet endpoint apres confirmation, puis naviguer vers `/go/play/{id}`.
3. Ne plus demarrer une partie via `/go/play` avec query params (redirect vers `/go`).
4. Stocker bot + time control dans la partie pour la reprise.

## Etapes detaillees

### 1) Backend: creer une partie au demarrage
Fichiers: `app/Http/Controllers/GoGameController.php`, `routes/web.php`, `app/Models/GoGame.php`, `database/migrations/...`

- Ajoute des champs en base:
  - `bot_id` (string, nullable)
  - `time_control_seconds` (unsigned int, nullable)
- Mets a jour le model `GoGame`:
  - `fillable` + `casts` pour les nouveaux champs.
- Ajoute un endpoint `POST /go/games/start` (ou similaire):
  - Valide `board_size`, `komi`, `bot_id`, `time_control_seconds`.
  - Cree un enregistrement avec:
    - `move_history = []`
    - `move_count = 0`
    - `black_captures = 0`, `white_captures = 0`
    - `black_score = 0`, `white_score = 0`
    - `is_finished = false`
    - `duration_seconds = 0`
  - Retourne `{ game_id: <id> }`.
- Dans `resume`, renvoie `bot_id` et `time_control_seconds` pour que le front ait les infos.

### 2) Frontend: demarrer la partie apres confirmation
Fichiers: `resources/js/pages/go/Index.vue`, `resources/js/composables/go/useGameSave.ts`

- Remplace `startGame()`:
  1. Ouvre un modal de confirmation (resume des choix).
  2. Si confirme, appelle l endpoint `POST /go/games/start`.
  3. Sur succes, `router.visit(\`/go/play/${gameId}\`)`.
  4. Sur erreur, affiche un message et reactive le bouton.
- Option simple: ajouter dans `useGameSave.ts` une methode `startGame()` qui fait le `fetch`.

### 3) Frontend: Play ne demarre que via un ID
Fichiers: `resources/js/pages/go/Play.vue`

- Assure toi que `Play` recupere `resumeGame` + `botId` + `timeControl` depuis le backend.
- Si `resumeGame` est absent, redirige vers `/go` (guard simple).

### 4) Routing: supprimer l effet "auto-start"
Fichiers: `routes/web.php`

- Remplace la route `GET /go/play` (avec query params) par:
  - un redirect vers `/go`, ou
  - une page neutre sans demarrage.
- Garde `GET /go/play/{goGame}` pour reprendre la partie.

## Points d attention (ne pas oublier)
- Auth: creer un ID implique un user. Si les guests peuvent jouer, il faut une strategie:
  - soit forcer la connexion,
  - soit rendre `user_id` nullable et gerer un mode guest.
- Reprise: sans `bot_id` et `time_control_seconds` en base, tu perds ces infos en resume.
- UX: garde un etat "loading" pendant l appel API et gere les timeouts.
- Historique: les liens `/go/play/{id}` existent deja dans `History.vue` et restent OK.

## Tests manuels (checklist rapide)
1. Clic Play -> confirmation -> start -> redirect `/go/play/{id}`.
2. Back navigateur -> retour `/go` (pas de nouvelle partie).
3. Forward -> meme partie (pas une nouvelle).
4. Quitter puis reprendre via Historique -> meme bot + time control.
5. Simuler erreur API -> bouton se reactive, pas de redirection.
