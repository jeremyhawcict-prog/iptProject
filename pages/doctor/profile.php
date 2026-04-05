<?php
$pageTitle = 'Doctor Profile';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['doctor']);
$user = getCurrentUser();
$profile = getUserById($user['id']);
$doctor  = getDoctorProfile($user['id']);

// Avg rating
$stmtRating = $pdo->prepare("SELECT AVG(f.rating) as avg_r, COUNT(f.id) as cnt FROM feedback f JOIN appointments a ON f.appointment_id=a.id JOIN time_slots t ON a.slot_id=t.id WHERE t.doctor_id=?");
$stmtRating->execute([$user['id']]);
$ratingRow = $stmtRating->fetch();
?>

<div style="max-width:700px;margin:0 auto;display:grid;gap:20px;">
  <div id="alert-container"></div>

  <!-- Photo + Info -->
  <div class="card-glass" style="padding:24px;text-align:center;">
    <div style="position:relative;display:inline-block;">
      <img id="profilePhoto" src="<?= profilePhotoUrl($profile['profile_photo'] ?? null) ?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--teal-core);" onerror="this.src='<?= BASE_URL ?>/assets/uploads/photos/default.svg'" />
      <label style="position:absolute;bottom:0;right:0;width:32px;height:32px;background:var(--teal-core);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;" title="Change photo">
        <i class="fa-solid fa-camera" style="font-size:.8rem;"></i>
        <input type="file" id="photoInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" />
      </label>
    </div>
    <h3 style="margin:12px 0 4px;">Dr. <?= htmlspecialchars($profile['full_name']) ?></h3>
    <span class="badge badge-success"><?= htmlspecialchars($doctor['specialization'] ?? 'General') ?></span>
    <?php if($ratingRow['avg_r']): ?>
      <div style="margin-top:8px;"><?= str_repeat('★', round($ratingRow['avg_r'])) . str_repeat('☆', 5-round($ratingRow['avg_r'])) ?> <span class="text-muted text-sm">(<?= $ratingRow['cnt'] ?> reviews)</span></div>
    <?php endif; ?>
  </div>

  <!-- Edit Profile -->
  <div class="card-glass" style="padding:24px;">
    <h3 style="margin:0 0 16px;"><i class="fa-solid fa-user-doctor" style="color:var(--teal-core);"></i> Professional Profile</h3>
    <form id="profileForm">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" id="fullName" class="form-control" value="<?= htmlspecialchars($profile['full_name']) ?>" required /></div>
      <div class="form-group"><label class="form-label">Specialization</label><input type="text" id="specialization" class="form-control" value="<?= htmlspecialchars($doctor['specialization'] ?? '') ?>" /></div>
      <div class="form-group"><label class="form-label">Phone</label><input type="text" id="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" /></div>
      <div class="form-group"><label class="form-label">Bio</label><textarea id="bio" class="form-control" rows="3"><?= htmlspecialchars($doctor['bio'] ?? '') ?></textarea></div>
      <div class="form-group"><label class="form-label">Available Days</label>
        <div style="display:flex;flex-wrap:wrap;gap:8px;">
          <?php
          $availDays = $doctor['available_days'] ?? '';
          $availArr = json_decode($availDays, true);
          if (!is_array($availArr)) {
              $availArr = $availDays ? explode(',', $availDays) : [];
          }
          $availArr = array_map('trim', $availArr);
          foreach(['Monday','Tuesday','Wednesday','Thursday','Friday','Saturday','Sunday'] as $day):
          ?>
            <label style="display:flex;align-items:center;gap:4px;cursor:pointer;"><input type="checkbox" class="avail-day" value="<?= $day ?>" <?= in_array($day,$availArr)?'checked':'' ?> /> <?= substr($day,0,3) ?></label>
          <?php endforeach; ?>
        </div>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Profile</button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="card-glass" style="padding:24px;">
    <h3 style="margin:0 0 16px;"><i class="fa-solid fa-lock" style="color:var(--teal-core);"></i> Change Password</h3>
    <form id="passwordForm">
      <div class="form-group"><label class="form-label">Current Password</label><input type="password" id="currentPwd" class="form-control" required /></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" id="newPwd" class="form-control" required minlength="8" /></div>
      <div class="form-group"><label class="form-label">Confirm</label><input type="password" id="confirmPwd" class="form-control" required /></div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Update Password</button>
    </form>
  </div>
</div>

<script>
(function(){
  document.getElementById('photoInput').addEventListener('change',function(){
    if(!this.files[0])return;
    var file = this.files[0];
    if(file.size > 2*1024*1024){ utils.showAlert('File exceeds 2 MB limit.','warning'); return; }
    // Preview before upload
    var reader = new FileReader();
    reader.onload = function(e){ document.getElementById('profilePhoto').src = e.target.result; };
    reader.readAsDataURL(file);
    // Upload
    var fd=new FormData();fd.append('photo', file);
    utils.apiUpload(utils.apiUrl('users/update-photo.php'),fd,function(e,d){
      if(d&&d.success){
        document.getElementById('profilePhoto').src=d.data.photo_url+'?t='+Date.now();
        utils.showToast('Photo updated!','success');
      } else {
        document.getElementById('profilePhoto').src='<?= profilePhotoUrl($profile['profile_photo'] ?? null) ?>';
        utils.showAlert(d?d.message:'Upload failed.','error');
      }
    });
  });

  document.getElementById('profileForm').addEventListener('submit',function(e){
    e.preventDefault();
    var days=[];document.querySelectorAll('.avail-day:checked').forEach(function(c){days.push(c.value);});
    var btn=this.querySelector('button[type=submit]');btn.disabled=true;
    utils.apiPost(utils.apiUrl('doctors/profile.php'),{
      full_name:document.getElementById('fullName').value.trim(),
      phone:document.getElementById('phone').value.trim(),
      specialization:document.getElementById('specialization').value.trim(),
      bio:document.getElementById('bio').value.trim(),
      available_days:days.join(',')
    },function(e,d){
      btn.disabled=false;
      if(d&&d.success) utils.showToast('Profile saved!','success');
      else utils.showAlert(d?d.message:'Failed.','error');
    });
  });

  document.getElementById('passwordForm').addEventListener('submit',function(e){
    e.preventDefault();
    if(document.getElementById('newPwd').value!==document.getElementById('confirmPwd').value){utils.showAlert('Passwords do not match.','warning');return;}
    var btn=this.querySelector('button[type=submit]');btn.disabled=true;
    utils.apiPost(utils.apiUrl('auth/change-password.php'),{
      current_password:document.getElementById('currentPwd').value,
      new_password:document.getElementById('newPwd').value
    },function(er,d){
      btn.disabled=false;
      if(d&&d.success){utils.showToast('Password changed!','success');e.target.reset();}
      else utils.showAlert(d?d.message:'Failed.','error');
    });
  });
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>