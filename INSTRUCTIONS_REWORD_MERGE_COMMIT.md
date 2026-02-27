# Changer le message du commit de merge (f51f631)

Le nouveau titre et la description sont dans **`rebase-reword-message.txt`**.

## Option A : Rebase interactif (recommandé)

À exécuter **dans ton terminal** (PowerShell ou Git Bash), à la racine du dépôt :

```bash
git rebase -i 6b84fed
```

1. Dans l’éditeur qui s’ouvre, repère la ligne du commit **f51f631** (Merge RamiUser into integration-rami-user…).
2. Remplace **`pick`** par **`reword`** (ou **`r`**) sur cette ligne. Sauvegarde et ferme l’éditeur.
3. Quand Git rouvre l’éditeur pour le message de ce commit, **remplace tout le texte** par le contenu de `rebase-reword-message.txt` :
   - Titre : `Merge RamiUser dans integration (conflits résolus, base GestionEvenements)`
   - Ligne vide puis le paragraphe de description.
4. Sauvegarde et ferme. Le rebase continue et réécrit l’historique.

Puis pousser la branche (écrasement de l’historique) :

```bash
git push origin integration --force
```

## Option B : filter-branch (sans ouvrir d’éditeur)

Dans **Git Bash** (pas PowerShell), à la racine du projet :

```bash
MSG_FILE="$(pwd)/rebase-reword-message.txt"
git filter-branch -f --msg-filter "if [ \"\$GIT_COMMIT\" = \"f51f6312262c40e7ef9dbf094a38305f8fb268f0\" ]; then cat \"$MSG_FILE\"; else cat; fi" 6b84fed..HEAD
```

Ensuite :

```bash
git push origin integration --force
```

---

Après avoir appliqué l’une des options, tu peux supprimer les fichiers temporaires si tu veux :

- `rebase-reword-message.txt`
- `rebase-reword-sequence.ps1`
- `rebase-reword-editor.ps1`
- `INSTRUCTIONS_REWORD_MERGE_COMMIT.md`
