# Stariks – Personīgo finansu pārvaldnieks

Stariks ir pilna cikla tīmekļa aplikācija, kas palīdz lietotājiem pārvaldīt savas kredītkartes, budžetus, transakcijas, investīcijas un veidot detalizētas atskaites. Projekts izstrādāts PHP (backend) un JavaScript (frontend) vidē, izmantojot Bootstrap 5 un Chart.js (grafiki pašlaik atskaitēs ir deaktivēti).

---

## Galvenās iespējas

| Funkcija | Apraksts |
|----------|----------|
| **Daudz-karšu atbalsts** | Vienlaicīgi pārvaldiet vairākas kredītkartes ar stingru datu izolāciju. |
| **Budžeti un brīdinājumi** | Uzstādiet mēneša/ gada limitus kategorijām; sistēma skaidri rāda izlietoto un atlikušo summu. |
| **Ātra transakciju reģistrācija** | Vienkārša veidlapa ar automātisku summas validāciju; transakcija momentā atjaunina kartes bilanci. |
| **Investīciju portfelis** | Sekojiet līdzi akcijām, fondiem, kripto utt., redziet ieguldīto un pašreizējo vērtību. |
| **Atskaites (PDF/DOC/XLSX)** | Ģenerējiet budžeta, transakciju vai investīciju atskaiti izvēlētam periodam; eksportējiet Word vai Excel formātā. |
| **Daudzvalodība** | LV / EN slēdzis (ietverta `lang.js`). |
| **Drošība** | Sesijas-balstīta autentifikācija, CSRF aizsardzība un validatori servera pusē. |

---

## Ātrā uzstādīšana

1. **Prasības**
   * PHP 8.1+
   * MySQL 8+
   * Composer
   * XAMPP vai cits Apache servars

2. **Projekta klonēšana**
```bash
cd htdocs
git clone https://github.com/lietotajs/stariks.git "gala darbs"
```

3. **Atkarību instalēšana**
```bash
cd "gala darbs/api"
composer install --no-dev
```

4. **Datu bāzes importēšana**
```bash
mysql -u root -p < db/init_stariks.sql
```

5. **Servera palaišana**
   * Ja izmantojat XAMPP, pārliecinieties, ka `apache` un `mysql` servisi darbojas.
   * Atveriet pārlūku: `http://localhost/gala%20darbs/login.html`

> Pirmreizējās pieslēgšanās dati atrodami `db/seed.sql` (ja ir izveidots) vai jāizveido lietotājs manuāli.

---

## Lietotāja ceļvedis (roadmap)

1. **Pieslēgšanās / Reģistrācija**  
   ‑ Izveidojiet kontu vai pieslēdzieties, izmantojot savu e-pastu un paroli.

2. **Kredītkartes pievienošana**  
   ‑ Dodieties uz sadaļu *Kartes* → *Pievienot karti*.  
   ‑ Norādiet banku, pēdējos 4 ciparus un sākuma bilanci.

3. **Budžetu izveide**  
   ‑ Sadaļa *Budžeti* → *Pievienot budžetu*.  
   ‑ Izvēlieties kategoriju, periodu (mēnesis/gads) un limitu.

4. **Transakciju pievienošana**  
   ‑ Sadaļa *Transakcijas* → *Pievienot transakciju*.  
   ‑ Aizpildiet summu, datumu, kategoriju un (ja vēlaties) piezīmi.

5. **Investīciju sekotājs**  
   ‑ Sadaļa *Investīcijas* → *Pievienot*.  
   ‑ Norādiet ticker, nosaukumu, daudzumu un ieguldīto summu.

6. **Atskaites ģenerēšana**  
   ‑ Sadaļa *Atskaites*.  
   ‑ Izvēlieties periodu un atskaites tipu (budžeti / transakcijas / investīcijas / visi).  
   ‑ Spiediet *Ģenerēt* un, ja nepieciešams, eksportējiet uz Word vai Excel.

---

## Nākotnes plāni

- [ ] Mobilā dizaina uzlabojumi un PWA atbalsts.  
- [ ] Push paziņojumi, ja budžeta atlikums tuvojas 0.  
- [ ] Automātiskā valūtu konvertācija (ECB API).  
- [ ] OAuth2 pieslēgšanās ar Google / Apple.  
- [ ] Integrācija ar banku Open Banking API automātiskai transakciju importēšanai.

---

