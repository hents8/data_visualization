# Dashboard Veille Média

![Dashboard Preview](docs/dashboard_preview.png)

## Description

Ce projet est une application **Symfony** pour visualiser des données issues d’un fichier Excel (`.xlsx`).  
Il permet de générer des **KPIs** et des **graphes (bar, doughnut)** pour comparer les indicateurs par **mois et année**, avec deux panels côte à côte.

### Fonctionnalités principales

- Lecture automatique des questions depuis Excel
- Affichage dynamique des résultats par panel gauche/droit
- Graphiques interactifs avec **Chart.js** :
  - **Doughnut** : pour `Media`, `Citée`, `Tonalité`
  - **Bar** : pour `Medium`, `Secteur`
- Filtrage par **année** et **mois** indépendamment pour chaque panel
- KPIs pour chaque question affichant le **total des réponses**

## Installation

1. **Cloner le projet**

```bash
git clone <votre_repo_url>
cd my_data_visualization_app
```

2. **Installer les dépendances**

```bash
composer install
```

3. **Préparer le fichier Excel**

- Placez votre fichier `Test.xlsx` à la racine du projet
- Assurez-vous que les colonnes suivantes existent :  
  `Year` (année)  
  `Month` (mois en chiffre)  
  `Media`, `Medium`, `Secteur`, `Citée`, `Tonalité`

4. **Lancer le serveur Symfony**

```bash
symfony server:start
```

- Accédez à l’application via [http://127.0.0.1:8000](http://127.0.0.1:8000)

## Utilisation

### Filtres

Chaque panel possède ses propres filtres **Année** et **Mois**.  
Par défaut, toutes les données sont affichées (sans filtre).

### Graphiques

- `Media`, `Citée`, `Tonalité` → **doughnut**  
- `Medium`, `Secteur` → **bar**

#### Exemple HTML d’un graphique (Panel Gauche)

```html
<div class="chart-container" style="width:100%; height:300px;">
  <canvas id="chartLeft1"></canvas>
</div>

<script>
const ctx = document.getElementById('chartLeft1').getContext('2d');
new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ['Oui', 'Non', 'Peut-être'],
        datasets: [{
            label: 'Réponses',
            data: [50, 30, 20],
            backgroundColor: ['#FF6384','#36A2EB','#FFCE56']
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { position: 'bottom' },
            datalabels: {
                color: '#000',
                anchor: 'end',
                align: 'top',
                formatter: (value) => value + '%'
            }
        }
    },
    plugins: [ChartDataLabels]
});
</script>
```

### KPI

Chaque question affiche un total de réponses :

```twig
{% include 'components/kpi.html.twig' with {
    title: 'Total réponses',
    value: result.totalResponses
} %}
```

## Structure du projet

```
my_data_visualization_app/
├─ src/
│  ├─ Controller/DataVisualizationController.php
│  ├─ Service/ExcelSurveyReader.php
│  └─ DTO/QuestionResult.php
├─ templates/
│  ├─ base.html.twig
│  ├─ dashboard/index.html.twig
│  └─ components/
│      ├─ chart.html.twig
│      ├─ chart_doughnut.html.twig
│      └─ kpi.html.twig
├─ Test.xlsx
├─ composer.json
└─ README.md
```

## Dépendances principales

- Symfony 6+
- PhpSpreadsheet (lecture Excel)
- Chart.js (graphiques interactifs)
- chartjs-plugin-datalabels (pour afficher les pourcentages sur les graphiques)

## Contribuer

1. **Créez une branche pour votre fonctionnalité**

```bash
git checkout -b feature/ma-nouvelle-fonction
```

2. **Faites vos modifications et committez**

```bash
git add .
git commit -m "Ajout de la fonctionnalité XYZ"
```

3. **Poussez votre branche**

```bash
git push origin feature/ma-nouvelle-fonction
```

## Licence

MIT License © 2026
