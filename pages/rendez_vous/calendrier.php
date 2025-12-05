<?php
/**
 * projet-medicare/pages/rendez_vous/calendrier.php
 * Vue calendrier des rendez-vous
 */

require_once '../../includes/header.php';
require_once '../../includes/functions.php';

// Vérifier les permissions
requireLogin();

// Obtenir les rendez-vous selon le rôle
$rendezVous = [];
if (hasRole('medecin')) {
    // Rendez-vous du médecin connecté
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom 
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            WHERE rv.id_medecin = (SELECT id FROM medecins WHERE id_utilisateur = :user_id) 
            ORDER BY rv.date_heure";
    $rendezVous = fetchAll($sql, ['user_id' => $_SESSION['user_id']]);
} else {
    // Tous les rendez-vous pour secrétaire et admin
    $sql = "SELECT rv.*, p.nom as patient_nom, p.prenom as patient_prenom, 
                   u.nom as medecin_nom, m.specialite 
            FROM rendez_vous rv 
            JOIN patients p ON rv.id_patient = p.id 
            JOIN medecins m ON rv.id_medecin = m.id 
            JOIN utilisateurs u ON m.id_utilisateur = u.id 
            ORDER BY rv.date_heure";
    $rendezVous = fetchAll($sql);
}

// Obtenir le mois et l'année actuels
$month = isset($_GET['month']) ? intval($_GET['month']) : date('n');
$year = isset($_GET['year']) ? intval($_GET['year']) : date('Y');

// Validation du mois et de l'année
$month = max(1, min(12, $month));
$year = max(2020, min(2030, $year));

// Calculer le mois précédent et suivant
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth < 1) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth > 12) {
    $nextMonth = 1;
    $nextYear++;
}

// Obtenir le premier jour du mois et le nombre de jours
$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = date('t', $firstDay);
$firstDayOfWeek = date('N', $firstDay) - 1; // 0 = Lundi, 6 = Dimanche

// Organiser les rendez-vous par jour
$eventsByDay = [];
foreach ($rendezVous as $rdv) {
    $day = date('j', strtotime($rdv['date_heure']));
    $monthRdv = date('n', strtotime($rdv['date_heure']));
    $yearRdv = date('Y', strtotime($rdv['date_heure']));
    
    if ($monthRdv == $month && $yearRdv == $year) {
        if (!isset($eventsByDay[$day])) {
            $eventsByDay[$day] = [];
        }
        $eventsByDay[$day][] = $rdv;
    }
}

// Noms des jours et mois
$daysOfWeek = ['Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam', 'Dim'];
$monthsOfYear = [
    1 => 'Janvier', 2 => 'Février', 3 => 'Mars', 4 => 'Avril',
    5 => 'Mai', 6 => 'Juin', 7 => 'Juillet', 8 => 'Août',
    9 => 'Septembre', 10 => 'Octobre', 11 => 'Novembre', 12 => 'Décembre'
];
?>

<div class="container">
    <div class="page-header">
        <h1><i class="fas fa-calendar-alt"></i> Calendrier des rendez-vous</h1>
        <div class="page-actions">
            <a href="rendez_vous.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> Vue liste
            </a>
            <button type="button" onclick="showAddForm()" class="btn btn-primary">
                <i class="fas fa-plus"></i> Nouveau rendez-vous
            </button>
        </div>
    </div>

    <!-- Navigation du calendrier -->
    <div class="card">
        <div class="card-body">
            <div class="calendar-nav">
                <a href="?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?>" 
                   class="btn btn-secondary">
                    <i class="fas fa-chevron-left"></i> Précédent
                </a>
                
                <h2 class="calendar-title">
                    <?php echo $monthsOfYear[$month] . ' ' . $year; ?>
                </h2>
                
                <a href="?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?>" 
                   class="btn btn-secondary">
                    Suivant <i class="fas fa-chevron-right"></i>
                </a>
            </div>
            
            <!-- Boutons de navigation rapide -->
            <div class="quick-nav">
                <a href="?month=<?php echo date('n'); ?>&year=<?php echo date('Y'); ?>" 
                   class="btn btn-outline-primary btn-sm">
                    Aujourd'hui
                </a>
                <a href="?month=<?php echo date('n', strtotime('+1 month')); ?>&year=<?php echo date('Y', strtotime('+1 month')); ?>" 
                   class="btn btn-outline-secondary btn-sm">
                    Mois prochain
                </a>
            </div>
        </div>
    </div>

    <!-- Calendrier -->
    <div class="card">
        <div class="card-body">
            <div class="calendar">
                <!-- En-têtes des jours -->
                <div class="calendar-header">
                    <?php foreach ($daysOfWeek as $day): ?>
                    <div class="calendar-day-header"><?php echo $day; ?></div>
                    <?php endforeach; ?>
                </div>
                
                <!-- Jours du calendrier -->
                <div class="calendar-body">
                    <!-- Jours vides avant le début du mois -->
                    <?php for ($i = 0; $i < $firstDayOfWeek; $i++): ?>
                    <div class="calendar-day empty"></div>
                    <?php endfor; ?>
                    
                    <!-- Jours du mois -->
                    <?php for ($day = 1; $day <= $daysInMonth; $day++): ?>
                    <?php
                    $isToday = ($day == date('j') && $month == date('n') && $year == date('Y'));
                    $isWeekend = (date('N', mktime(0, 0, 0, $month, $day, $year)) >= 6);
                    $hasEvents = isset($eventsByDay[$day]) && !empty($eventsByDay[$day]);
                    ?>
                    <div class="calendar-day <?php echo $isToday ? 'today' : ''; ?> <?php echo $isWeekend ? 'weekend' : ''; ?> <?php echo $hasEvents ? 'has-events' : ''; ?>">
                        <div class="calendar-day-number">
                            <?php echo $day; ?>
                            <?php if ($isToday): ?>
                            <span class="today-label">Aujourd'hui</span>
                            <?php endif; ?>
                        </div>
                        
                        <?php if ($hasEvents): ?>
                        <div class="calendar-events">
                            <?php 
                            $dayEvents = array_slice($eventsByDay[$day], 0, 3); // Limiter à 3 événements
                            foreach ($dayEvents as $event): 
                            ?>
                            <div class="calendar-event status-<?php echo $event['statut']; ?>" 
                                 title="<?php echo htmlspecialchars($event['patient_nom'] . ' ' . $event['patient_prenom']); ?> - <?php echo date('H:i', strtotime($event['date_heure'])); ?>">
                                <div class="event-time"><?php echo date('H:i', strtotime($event['date_heure'])); ?></div>
                                <div class="event-patient"><?php echo htmlspecialchars($event['patient_nom']); ?></div>
                                <?php if (!hasRole('medecin')): ?>
                                <div class="event-doctor"><?php echo htmlspecialchars($event['medecin_nom']); ?></div>
                                <?php endif; ?>
                            </div>
                            <?php endforeach; ?>
                            
                            <?php if (count($eventsByDay[$day]) > 3): ?>
                            <div class="more-events">
                                +<?php echo count($eventsByDay[$day]) - 3; ?> autre(s)
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endfor; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Légende -->
    <div class="card">
        <div class="card-body">
            <h4>Légende</h4>
            <div class="legend">
                <div class="legend-item">
                    <div class="legend-color status-planifie"></div>
                    <span>Planifié</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-confirme"></div>
                    <span>Confirmé</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color status-annule"></div>
                    <span>Annulé</span>
                </div>
                <div class="legend-item">
                    <div class="legend-color today-indicator"></div>
                    <span>Aujourd'hui</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de détails d'un événement -->
<div id="eventModal" class="modal" style="display: none;">
    <div class="modal-content">
        <div class="modal-header">
            <h3><i class="fas fa-calendar-check"></i> Détails du rendez-vous</h3>
            <button class="modal-close">&times;</button>
        </div>
        <div class="modal-body">
            <div id="eventDetails">
                <div class="text-center">
                    <i class="fas fa-spinner fa-spin"></i> Chargement...
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-secondary modal-close">Fermer</button>
            <div id="eventActions"></div>
        </div>
    </div>
</div>

<style>
.page-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 2rem;
    flex-wrap: wrap;
    gap: 1rem;
}

.page-header h1 {
    color: var(--primary-color);
    margin: 0;
}

.page-actions {
    display: flex;
    gap: 0.5rem;
    flex-wrap: wrap;
}

.calendar-nav {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1rem;
}

.calendar-title {
    color: var(--primary-color);
    margin: 0;
    text-align: center;
}

.quick-nav {
    text-align: center;
    margin-top: 1rem;
}

.calendar {
    border: 1px solid var(--light-color);
    border-radius: var(--border-radius);
    overflow: hidden;
}

.calendar-header {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
    background-color: var(--primary-color);
    color: white;
}

.calendar-day-header {
    padding: 1rem 0.5rem;
    text-align: center;
    font-weight: 600;
    border-right: 1px solid rgba(255, 255, 255, 0.1);
}

.calendar-day-header:last-child {
    border-right: none;
}

.calendar-body {
    display: grid;
    grid-template-columns: repeat(7, 1fr);
}

.calendar-day {
    min-height: 100px;
    border: 1px solid var(--light-color);
    border-top: none;
    padding: 0.5rem;
    position: relative;
    background-color: white;
}

.calendar-day.empty {
    background-color: #f8f9fa;
}

.calendar-day.today {
    background-color: #e3f2fd;
}

.calendar-day.weekend {
    background-color: #fafafa;
}

.calendar-day-number {
    font-weight: 600;
    margin-bottom: 0.25rem;
    position: relative;
}

.today-label {
    position: absolute;
    top: -20px;
    left: 0;
    font-size: 0.7rem;
    background-color: var(--secondary-color);
    color: white;
    padding: 2px 6px;
    border-radius: 3px;
    font-weight: normal;
}

.calendar-events {
    font-size: 0.75rem;
}

.calendar-event {
    background-color: var(--light-color);
    border-left: 3px solid;
    padding: 2px 4px;
    margin-bottom: 2px;
    border-radius: 3px;
    cursor: pointer;
    transition: var(--transition);
}

.calendar-event:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
}

.calendar-event.status-planifie {
    border-left-color: var(--warning-color);
}

.calendar-event.status-confirme {
    border-left-color: var(--success-color);
}

.calendar-event.status-annule {
    border-left-color: var(--danger-color);
    opacity: 0.6;
}

.event-time {
    font-weight: 600;
    color: var(--dark-color);
}

.event-patient {
    color: var(--primary-color);
}

.event-doctor {
    color: var(--gray-color);
    font-size: 0.7rem;
}

.more-events {
    text-align: center;
    color: var(--gray-color);
    font-style: italic;
    margin-top: 2px;
}

.legend {
    display: flex;
    gap: 2rem;
    flex-wrap: wrap;
}

.legend-item {
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.legend-color {
    width: 20px;
    height: 20px;
    border-radius: 3px;
}

.legend-color.status-planifie {
    background-color: var(--warning-color);
}

.legend-color.status-confirme {
    background-color: var(--success-color);
}

.legend-color.status-annule {
    background-color: var(--danger-color);
}

.legend-color.today-indicator {
    background-color: #e3f2fd;
    border: 2px solid var(--secondary-color);
}

@media (max-width: 768px) {
    .page-header {
        flex-direction: column;
        align-items: stretch;
    }
    
    .page-actions {
        justify-content: center;
    }
    
    .calendar-nav {
        flex-direction: column;
        gap: 1rem;
    }
    
    .calendar-day {
        min-height: 80px;
        padding: 0.25rem;
    }
    
    .calendar-day-header {
        padding: 0.5rem 0.25rem;
        font-size: 0.8rem;
    }
    
    .legend {
        justify-content: center;
    }
    
    .calendar-event {
        font-size: 0.7rem;
    }
}
</style>

<script>
function showAddForm() {
    window.location.href = 'rendez_vous.php?add=1';
}

function showEventDetails(eventId) {
    document.getElementById('eventModal').style.display = 'block';
    
    // Charger les détails via AJAX
    fetch(`../../api/rendez_vous_details.php?id=${eventId}`)
        .then(response => response.json())
        .then(data => {
            let html = `
                <div class="event-detail">
                    <p><strong>Date:</strong> ${data.date_heure}</p>
                    <p><strong>Patient:</strong> ${data.patient_nom} ${data.patient_prenom}</p>
            `;
            
            if (!document.querySelector('[data-role="medecin"]')) {
                html += `<p><strong>Médecin:</strong> ${data.medecin_nom}</p>`;
            }
            
            html += `
                    <p><strong>Statut:</strong> <span class="badge status-${data.statut}">${data.statut}</span></p>
                    <p><strong>Notes:</strong> ${data.notes || 'Aucune'}</p>
                </div>
            `;
            
            document.getElementById('eventDetails').innerHTML = html;
            
            // Actions selon le rôle et le statut
            let actions = '';
            if (data.statut === 'planifie' && document.querySelector('[data-role="medecin"]')) {
                actions += `<a href="../consultations/consultation_form.php?rdv_id=${data.id}" class="btn btn-success">
                    <i class="fas fa-stethoscope"></i> Consulter
                </a>`;
            }
            
            if (document.querySelector('[data-role="secretaire"], [data-role="admin"]')) {
                actions += `<a href="rendez_vous.php?edit=${data.id}" class="btn btn-primary">
                    <i class="fas fa-edit"></i> Modifier
                </a>`;
            }
            
            document.getElementById('eventActions').innerHTML = actions;
        })
        .catch(error => {
            document.getElementById('eventDetails').innerHTML = 
                '<p class="text-danger">Erreur lors du chargement des détails.</p>';
        });
}

// Ajouter des data-attributes pour le rôle
document.addEventListener('DOMContentLoaded', function() {
    document.body.setAttribute('data-role', '<?php echo $_SESSION['user_role']; ?>');
});
</script>

<?php require_once '../../includes/footer.php'; ?>