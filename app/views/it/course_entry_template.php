<!-- This is a template file for course entries - will be used by JavaScript -->
<div class="course-entry" data-course-index="{{INDEX}}" style="border: 2px solid var(--border-color); border-radius: 12px; padding: 1.5rem; margin-bottom: 1.5rem; background: var(--bg-secondary);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
        <h3 style="margin: 0; color: var(--text-primary);">
            <i class="fas fa-book"></i> Course Entry <span class="course-entry-number">{{NUMBER}}</span>
        </h3>
        <button type="button" class="btn btn-sm btn-danger" onclick="removeCourseEntry(this)" style="padding: 0.5rem 1rem;">
            <i class="fas fa-times"></i> Remove Course
        </button>
    </div>
    
    <div class="form-grid">
        <div class="form-group">
            <label class="form-label">Course *</label>
            <select name="courses[{{INDEX}}][course_id]" class="form-input course-select" required onchange="loadSectionsForCourse({{INDEX}}, this.value)">
                <option value="">Select Course</option>
                <?php foreach ($courses as $course): ?>
                    <option value="<?= $course['course_id'] ?>">
                        <?= htmlspecialchars($course['course_code']) ?> - <?= htmlspecialchars($course['name']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="form-group full-width">
            <label class="form-label">Doctors (Instructors) *</label>
            <div class="checkbox-list doctors-list-{{INDEX}}" style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-tertiary);">
                <?php foreach ($doctors as $doctor): ?>
                    <label class="checkbox-item" style="display: flex; align-items: center; gap: 0.75rem; padding: 0.75rem; border-bottom: 1px solid var(--border-color); cursor: pointer; transition: background-color 0.2s;" 
                           onmouseover="this.style.backgroundColor='rgba(59, 130, 246, 0.2)'"
                           onmouseout="this.style.backgroundColor='transparent'">
                        <input type="checkbox" name="courses[{{INDEX}}][doctor_ids][]" value="<?= $doctor['doctor_id'] ?>" class="doctor-checkbox" style="width: 18px; height: 18px; cursor: pointer;">
                        <div>
                            <div style="font-weight: 500; color: var(--text-primary);">
                                <?= htmlspecialchars($doctor['first_name']) ?> <?= htmlspecialchars($doctor['last_name']) ?>
                            </div>
                            <?php if (!empty($doctor['email'])): ?>
                                <div style="font-size: 0.85rem; color: var(--text-secondary);">
                                    <?= htmlspecialchars($doctor['email']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </label>
                <?php endforeach; ?>
            </div>
        </div>

        <div class="form-group full-width">
            <label class="form-label">Section/Session Numbers *</label>
            <div class="checkbox-list sections-list-{{INDEX}}" style="max-height: 200px; overflow-y: auto; border: 1px solid var(--border-light); border-radius: 8px; padding: 1rem; background: var(--bg-tertiary);">
                <div class="section-numbers-placeholder-{{INDEX}}" style="text-align: center; padding: 2rem; color: var(--text-muted);">
                    <i class="fas fa-info-circle"></i> Select a course first to see available section numbers
                </div>
            </div>
            <input type="text" class="new-section-input-{{INDEX}} form-input" style="margin-top: 0.5rem;" placeholder="Add new section number (e.g., 001, L01, LAB01)">
            <button type="button" class="btn btn-outline" onclick="addNewSectionNumber({{INDEX}})" style="margin-top: 0.5rem; padding: 0.5rem 1rem;">
                <i class="fas fa-plus"></i> Add Section Number
            </button>
        </div>

        <div class="form-group">
            <label class="form-label">Room *</label>
            <input type="text" name="courses[{{INDEX}}][room]" class="form-input" required placeholder="e.g., A101">
        </div>

        <div class="form-group">
            <label class="form-label">Capacity *</label>
            <input type="number" name="courses[{{INDEX}}][capacity]" class="form-input" required value="30" min="1">
        </div>

        <div class="form-group">
            <label class="form-label">Default Session Type</label>
            <select class="default-session-type-{{INDEX}} form-input">
                <option value="lecture">Lecture</option>
                <option value="lab">Lab</option>
                <option value="tutorial">Tutorial</option>
                <option value="section">Section</option>
                <option value="seminar">Seminar</option>
                <option value="workshop">Workshop</option>
            </select>
        </div>

        <div class="form-group full-width">
            <label class="form-label">Schedule Days *</label>
            <div class="days-selector">
                <?php 
                $days = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];
                foreach ($days as $day): ?>
                    <div class="day-option">
                        <input type="checkbox" name="courses[{{INDEX}}][days][]" value="<?= $day ?>" 
                               id="day_{{INDEX}}_<?= strtolower($day) ?>" 
                               class="day-checkbox" 
                               onchange="updateDayScheduleForCourse({{INDEX}}, '<?= $day ?>')">
                        <label for="day_{{INDEX}}_<?= strtolower($day) ?>" class="day-label">
                            <span class="day-name"><?= $day ?></span>
                        </label>
                        <div class="day-schedule" id="schedule_{{INDEX}}_<?= strtolower($day) ?>" style="display: none;">
                            <div class="sessions-container" data-day="<?= strtolower($day) ?>" data-course-index="{{INDEX}}">
                                <!-- Sessions will be added here dynamically -->
                            </div>
                            <button type="button" class="btn btn-sm btn-outline" onclick="addSessionForCourse({{INDEX}}, '<?= strtolower($day) ?>')" style="margin-top: 0.5rem; width: 100%;">
                                <i class="fas fa-plus"></i> Add Session
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

