<?php
$pageTitle = 'My Profile';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['patient']);
$user = getCurrentUser();
$profile = getUserById($user['id']);
?>

<div style="max-width:700px;margin:0 auto;display:grid;gap:20px;">
  <div id="alert-container"></div>

  <!-- Photo + Basic Info -->
  <div class="card-glass" style="padding:24px;text-align:center;">
    <div style="position:relative;display:inline-block;">
      <img id="profilePhoto" src="<?= profilePhotoUrl($profile['profile_photo'] ?? null) ?>" style="width:100px;height:100px;border-radius:50%;object-fit:cover;border:3px solid var(--teal-core);" onerror="this.src='<?= BASE_URL ?>/assets/uploads/photos/default.svg'" />
      <label style="position:absolute;bottom:0;right:0;width:32px;height:32px;background:var(--teal-core);border-radius:50%;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#fff;" title="Change photo">
        <i class="fa-solid fa-camera" style="font-size:.8rem;"></i>
        <input type="file" id="photoInput" accept="image/jpeg,image/png,image/gif,image/webp" style="display:none;" />
      </label>
    </div>
    <h3 style="margin:12px 0 4px;"><?= htmlspecialchars($profile['full_name']) ?></h3>
    <span class="badge badge-primary">Patient</span>
  </div>

  <!-- Edit Profile -->
  <div class="card-glass" style="padding:24px;">
    <h3 style="margin:0 0 16px;"><i class="fa-solid fa-user-pen" style="color:var(--teal-core);"></i> Edit Profile</h3>
    <form id="profileForm">
      <div class="form-group"><label class="form-label">Full Name</label><input type="text" id="fullName" class="form-control" value="<?= htmlspecialchars($profile['full_name']) ?>" required /></div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="form-group"><label class="form-label">Phone</label><input type="text" id="phone" class="form-control" value="<?= htmlspecialchars($profile['phone'] ?? '') ?>" /></div>
        <div class="form-group"><label class="form-label">Date of Birth</label><input type="date" id="dob" class="form-control" value="<?= htmlspecialchars($profile['date_of_birth'] ?? '') ?>" /></div>
      </div>
      <div class="form-group"><label class="form-label">Gender</label>
        <select id="gender" class="form-control">
          <option value="">Select</option>
          <option value="male" <?= ($profile['gender']??'')==='male'?'selected':'' ?>>Male</option>
          <option value="female" <?= ($profile['gender']??'')==='female'?'selected':'' ?>>Female</option>
          <option value="other" <?= ($profile['gender']??'')==='other'?'selected':'' ?>>Other</option>
        </select>
      </div>
      <div class="form-group"><label class="form-label">Address</label><textarea id="address" class="form-control" rows="2"><?= htmlspecialchars($profile['address'] ?? '') ?></textarea></div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-save"></i> Save Changes</button>
    </form>
  </div>

  <!-- Change Password -->
  <div class="card-glass" style="padding:24px;">
    <h3 style="margin:0 0 16px;"><i class="fa-solid fa-lock" style="color:var(--teal-core);"></i> Change Password</h3>
    <form id="passwordForm">
      <div class="form-group"><label class="form-label">Current Password</label><input type="password" id="currentPwd" class="form-control" required /></div>
      <div class="form-group"><label class="form-label">New Password</label><input type="password" id="newPwd" class="form-control" required minlength="8" /></div>
      <div class="form-group"><label class="form-label">Confirm New Password</label><input type="password" id="confirmPwd" class="form-control" required /></div>
      <button type="submit" class="btn btn-primary"><i class="fa-solid fa-key"></i> Update Password</button>
    </form>
  </div>
</div>

<script>
(function(){
  // Photo upload with preview
  document.getElementById('photoInput').addEventListener('change', function(){
    if(!this.files[0]) return;
    var file = this.files[0];
    if(file.size > 2*1024*1024){ utils.showAlert('File exceeds 2 MB limit.','warning'); return; }
    // Preview before upload
    var reader = new FileReader();
    reader.onload = function(e){ document.getElementById('profilePhoto').src = e.target.result; };
    reader.readAsDataURL(file);
    // Upload
    var fd=new FormData(); fd.append('photo', file);
    utils.apiUpload(utils.apiUrl('users/update-photo.php'), fd, function(err,data){
      if(data&&data.success){
        document.getElementById('profilePhoto').src = data.data.photo_url+'?t='+Date.now();
        utils.showToast('Photo updated!','success');
      } else {
        // Revert preview on failure
        document.getElementById('profilePhoto').src = '<?= profilePhotoUrl($profile['profile_photo'] ?? null) ?>';
        utils.showAlert(data?data.message:'Upload failed.','error');
      }
    });
  });

  // Profile form
  document.getElementById('profileForm').addEventListener('submit', function(e){
    e.preventDefault();
    var btn=this.querySelector('button[type=submit]'); btn.disabled=true;
    utils.apiPost(utils.apiUrl('users/update-profile.php'),{
      full_name: document.getElementById('fullName').value.trim(),
      phone: document.getElementById('phone').value.trim(),
      date_of_birth: document.getElementById('dob').value,
      gender: document.getElementById('gender').value,
      address: document.getElementById('address').value.trim()
    },function(err,data){
      btn.disabled=false;
      if(data&&data.success) utils.showToast('Profile saved!','success');
      else utils.showAlert(data?data.message:'Failed.','error');
    });
  });

  // Password form
  document.getElementById('passwordForm').addEventListener('submit', function(e){
    e.preventDefault();
    var np=document.getElementById('newPwd').value, cp=document.getElementById('confirmPwd').value;
    if(np!==cp){utils.showAlert('Passwords do not match.','warning');return;}
    var btn=this.querySelector('button[type=submit]'); btn.disabled=true;
    utils.apiPost(utils.apiUrl('auth/change-password.php'),{
      current_password: document.getElementById('currentPwd').value,
      new_password: np
    },function(err,data){
      btn.disabled=false;
      if(data&&data.success){utils.showToast('Password changed!','success');e.target.reset();}
      else utils.showAlert(data?data.message:'Failed.','error');
    });
  });
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>