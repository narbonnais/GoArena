# Brief Pixel Art - Jeu de Go

## Contexte du projet

Application web de jeu de Go (jeu de plateau asiatique). L'interface actuelle est minimaliste et vectorielle. On souhaite la remplacer par un style **pixel art** pour un rendu unique et rétro.

**Plateformes cibles** : Web (desktop + mobile)

---

## Assets demandés

### 1. Pierres de jeu (PRIORITÉ HAUTE)

| Asset | Dimensions | Format | Notes |
|-------|-----------|--------|-------|
| Pierre noire | 36x36 px | PNG-24 (transparent) | Inclure un léger effet 3D/reflet |
| Pierre blanche | 36x36 px | PNG-24 (transparent) | Inclure un léger effet 3D/reflet |
| Pierre noire (hover) | 36x36 px | PNG-24 (transparent) | Version semi-transparente pour prévisualisation |
| Pierre blanche (hover) | 36x36 px | PNG-24 (transparent) | Version semi-transparente pour prévisualisation |

**Optionnel** : Ombre portée en fichier séparé (36x36 px) ou intégrée avec offset de 2px

### 2. Marqueurs

| Asset | Dimensions | Format | Notes |
|-------|-----------|--------|-------|
| Marqueur dernière pierre (noir) | 12x12 px | PNG-24 (transparent) | Cercle/carré contrastant sur pierre noire |
| Marqueur dernière pierre (blanc) | 12x12 px | PNG-24 (transparent) | Cercle/carré contrastant sur pierre blanche |
| Point étoile (hoshi) | 8x8 px | PNG-24 (transparent) | Point de repère sur le plateau |

### 3. Plateau de jeu

| Asset | Dimensions | Format | Notes |
|-------|-----------|--------|-------|
| Texture bois (tileable) | 40x40 px | PNG-24 | Doit pouvoir se répéter sans jointure visible |
| **OU** Plateau complet 9x9 | 380x380 px | PNG-24 | Avec grille intégrée |
| **OU** Plateau complet 19x19 | 780x780 px | PNG-24 | Avec grille intégrée |
| Bordure/cadre (optionnel) | Variable | PNG-24 | Coins + bords répétables |

### 4. Éléments UI (OPTIONNEL)

| Asset | Dimensions | Format | Notes |
|-------|-----------|--------|-------|
| Bol à pierres (noir) | 64x64 px | PNG-24 (transparent) | Pour afficher les prisonniers |
| Bol à pierres (blanc) | 64x64 px | PNG-24 (transparent) | Pour afficher les prisonniers |
| Icône passer son tour | 24x24 px | PNG-24 (transparent) | Bouton "Pass" |
| Icône abandonner | 24x24 px | PNG-24 (transparent) | Bouton "Resign" |
| Icône annuler coup | 24x24 px | PNG-24 (transparent) | Bouton "Undo" |

---

## Spécifications techniques

### Format des fichiers
- **PNG-24** avec canal alpha (transparence)
- Pas de compression lossy (pas de JPG)
- Fond transparent sauf pour le plateau

### Palette de couleurs (référence actuelle)

```
Plateau (bois)      : #DDB06D
Lignes de grille    : #1A1A1A
Pierre noire        : #1A1A1A
Pierre blanche      : #F5F5F5
Coordonnées         : #5C4A32
```

Tu peux t'en inspirer ou proposer ta propre palette pixel art, tant que :
- Le contraste noir/blanc des pierres reste clair
- Le plateau reste dans des tons bois/chauds
- L'ensemble reste lisible

### Dimensions importantes

```
Taille d'une cellule : 40x40 pixels
Diamètre d'une pierre : 36 pixels (90% de la cellule)
Espacement grille : 40 pixels entre chaque ligne

Tailles de plateau :
- 9x9   → 8 cellules = 320px + marges = ~380px
- 13x13 → 12 cellules = 480px + marges = ~540px
- 19x19 → 18 cellules = 720px + marges = ~780px
```

### Résolution

Idéalement, livre les assets en **2x** (72x72 pour les pierres) pour :
- Meilleur rendu sur écrans Retina/HiDPI
- Flexibilité pour le scaling
- On réduira ensuite si besoin

---

## Style recherché

### Références visuelles
- Pixel art style 16-bit / SNES
- Palette limitée (16-32 couleurs max pour la cohérence)
- Léger effet de profondeur sur les pierres (pas flat)
- Ambiance : traditionnel japonais revisité pixel art

### À éviter
- Trop de détails (ça pixelise mal en petit)
- Anti-aliasing excessif (on veut voir les pixels !)
- Gradients smooth (préférer le dithering)

---

## Livrables attendus

```
/assets
  /stones
    stone-black.png
    stone-black@2x.png
    stone-white.png
    stone-white@2x.png
    stone-black-hover.png
    stone-white-hover.png
  /markers
    marker-last-black.png
    marker-last-white.png
    hoshi.png
  /board
    board-texture.png (tileable)
    -- OU --
    board-9x9.png
    board-13x13.png
    board-19x19.png
  /ui (optionnel)
    bowl-black.png
    bowl-white.png
    icon-pass.png
    icon-resign.png
    icon-undo.png
```

---

## Questions à clarifier ensemble

1. **Plateau** : Tu préfères faire une texture tileable ou des plateaux complets ?
2. **Animations** : Est-ce qu'on veut des sprites animés (pierre qui tombe, capture, etc.) ?
3. **Thèmes** : Un seul style ou plusieurs variantes (ex: bois clair/foncé) ?
4. **Coordonnées** : Tu veux dessiner une police pixel art pour A-T et 1-19 ?

---

## Contact

Pour toute question sur les specs techniques, n'hésite pas !

Deadline souhaitée : _____________
Budget : _____________
