# ⚡ QUICK REFERENCE - JUNIORS UNIQUEMENT

## 🎯 EN BREF

**Changement** : Programme réservé aux juniors de 2 à 17 ans UNIQUEMENT

**Fichiers modifiés** : 3
1. `public/partials/juniorgolfkenya-registration-form.php`
2. `admin/partials/juniorgolfkenya-admin-member-edit.php`
3. `admin/partials/juniorgolfkenya-admin-members.php`

**Base de données** : ✅ AUCUNE modification

---

## ✅ CHECKLIST RAPIDE

### Test 1 : Public
- [ ] Ouvrir `/register`
- [ ] Voir bandeau violet "Programme Junior Golf Kenya"
- [ ] Saisir date → Message temps réel
- [ ] Âge < 2 → Message rouge + blocage
- [ ] Âge >= 18 → Message rouge + blocage
- [ ] Âge valide → Message vert + OK
- [ ] Section parent visible + obligatoire
- [ ] Soumettre → Compte créé en "junior"

### Test 2 : Admin Ajout
- [ ] Ouvrir admin membres → Add New
- [ ] Info box bleue "Junior Golf Kenya"
- [ ] Date obligatoire
- [ ] Sans date → Erreur
- [ ] Âge invalide → Erreur
- [ ] Valide → Créé en "junior"

### Test 3 : Admin Édition
- [ ] Éditer un membre
- [ ] Voir "Junior (X ans)"
- [ ] Ancien type → Avertissement
- [ ] Save → Converti en "junior"

---

## 🚨 TESTS CRITIQUES

```
✅ Date < 2 ans → REFUSÉ
✅ Date >= 18 ans → REFUSÉ
✅ 2-17 ans → ACCEPTÉ
✅ Sans parent → REFUSÉ
✅ Type forcé "junior" → OK
```

---

## 📊 VÉRIFICATION DB

```sql
-- Dernier membre créé
SELECT * FROM wp_jgf_members 
ORDER BY id DESC LIMIT 1;

-- Doit avoir : membership_type = 'junior'
```

---

## 📁 DOCUMENTATION

- **Technique** : `JUNIOR_ONLY_IMPLEMENTATION.md`
- **Utilisateur** : `README_JUNIORS_ONLY.md`
- **Spec complète** : `JUNIOR_ONLY_REVIEW.md`
- **Test HTML** : `test-juniors-only.html`

---

## 🔧 ROLLBACK (si besoin)

```bash
# Restaurer les fichiers d'origine depuis Git
git checkout HEAD -- public/partials/juniorgolfkenya-registration-form.php
git checkout HEAD -- admin/partials/juniorgolfkenya-admin-member-edit.php
git checkout HEAD -- admin/partials/juniorgolfkenya-admin-members.php
```

---

**✅ Révision complète**  
**📅 11/10/2025**
