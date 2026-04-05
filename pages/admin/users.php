<?php
$pageTitle = 'Manage Users';
$pageCSS   = ['dashboard.css'];
require_once __DIR__ . '/../../includes/header.php';
requireRole(['admin']);
?>

<div class="card-glass" style="padding:24px;">
  <div style="display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:12px;margin-bottom:20px;">
    <h2 style="margin:0;"><i class="fa-solid fa-users-gear" style="color:var(--teal-core);"></i> Manage Users</h2>
    <button class="btn btn-primary" onclick="openAddUser()"><i class="fa-solid fa-user-plus"></i> Add User</button>
  </div>
  <div id="alert-container"></div>

  <!-- Role Tabs -->
  <div style="display:flex;gap:6px;flex-wrap:wrap;margin-bottom:16px;">
    <button class="btn btn-sm btn-outline role-tab active" data-role="">All</button>
    <button class="btn btn-sm btn-outline role-tab" data-role="patient">Patients</button>
    <button class="btn btn-sm btn-outline role-tab" data-role="doctor">Doctors</button>
    <button class="btn btn-sm btn-outline role-tab" data-role="admin">Admins</button>
  </div>

  <div style="margin-bottom:12px;">
    <input type="text" id="userSearch" class="form-control" placeholder="Search by name or email…" style="max-width:320px;" />
  </div>

  <div class="table-responsive">
    <table class="data-table" id="usersTable">
      <thead><tr><th>#</th><th>Name</th><th>Email</th><th>Role</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody id="usersBody"><tr><td colspan="6" class="text-center text-muted">Loading…</td></tr></tbody>
    </table>
  </div>
  <div id="pagination" style="display:flex;justify-content:center;gap:6px;margin-top:16px;"></div>
</div>

<!-- Add/Edit Modal -->
<div class="modal-overlay" id="userModal">
  <div class="modal" style="max-width:480px;">
    <div class="modal-header"><h3 id="userModalTitle">Add User</h3><button class="modal-close" onclick="closeModal('userModal')">&times;</button></div>
    <div class="modal-body">
      <input type="hidden" id="editUserId" />
      <div class="form-group"><label class="form-label">Full Name *</label><input type="text" id="uName" class="form-control" required /></div>
      <div class="form-group"><label class="form-label">Email *</label><input type="email" id="uEmail" class="form-control" required /></div>
      <div class="form-group" id="pwdGroup"><label class="form-label">Password *</label><input type="password" id="uPassword" class="form-control" minlength="8" /></div>
      <div class="form-group"><label class="form-label">Role</label>
        <select id="uRole" class="form-control"><option value="patient">Patient</option><option value="doctor">Doctor</option><option value="admin">Admin</option></select>
      </div>
      <div class="form-group"><label class="form-label">Phone</label><input type="text" id="uPhone" class="form-control" /></div>
    </div>
    <div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('userModal')">Cancel</button><button class="btn btn-primary" id="submitUser">Save</button></div>
  </div>
</div>

<style>
.role-tab{background:transparent;border:1px solid var(--input-border);border-radius:8px;font-weight:600;transition:all .2s;}
.role-tab.active,.role-tab:hover{background:var(--teal-core);color:#fff;border-color:var(--teal-core);}
</style>

<script>
(function(){
  var currentPage=1, currentRole='';

  function load(page){
    currentPage=page||1;
    var params={page:currentPage,per_page:15};
    if(currentRole) params.role=currentRole;
    var q=document.getElementById('userSearch').value.trim();
    if(q) params.search=q;
    utils.apiGet(utils.apiUrl('users/list.php'),params,function(err,data){
      var tb=document.getElementById('usersBody');
      if(!data||!data.success||!data.data.users||!data.data.users.length){
        tb.innerHTML='<tr><td colspan="6" class="text-center text-muted">No users found.</td></tr>';
        document.getElementById('pagination').innerHTML='';return;
      }
      tb.innerHTML='';
      data.data.users.forEach(function(u,i){
        var n=data.data.pagination?((data.data.pagination.current_page-1)*data.data.pagination.per_page+i+1):(i+1);
        var isActive = u.is_active!==0 && u.is_active!=='0';
        tb.insertAdjacentHTML('beforeend',
          '<tr><td>'+n+'</td>'
          +'<td>'+utils.escapeHtml(u.full_name)+'</td>'
          +'<td>'+utils.escapeHtml(u.email)+'</td>'
          +'<td><span class="badge badge-'+(u.role==='admin'?'danger':(u.role==='doctor'?'success':'primary'))+'">'+u.role+'</span></td>'
          +'<td><span class="badge '+(isActive?'badge-success':'badge-danger')+'">'+(isActive?'Active':'Inactive')+'</span></td>'
          +'<td style="display:flex;gap:4px;">'
          +'<button class="btn btn-sm btn-outline" onclick=\'openEditUser('+JSON.stringify(u).replace(/'/g,"&#39;")+')\' ><i class="fa-solid fa-pen"></i></button>'
          +'<button class="btn btn-sm '+(isActive?'btn-danger':'btn-success')+'" onclick="toggleUser('+u.id+','+(isActive?0:1)+')"><i class="fa-solid fa-'+(isActive?'ban':'check')+'"></i></button>'
          +'</td></tr>'
        );
      });
      var p=data.data.pagination,el=document.getElementById('pagination');
      if(!p||p.total_pages<=1){el.innerHTML='';return;}
      var h='';for(var x=1;x<=p.total_pages;x++) h+='<button class="btn btn-sm '+(x===p.current_page?'btn-primary':'btn-outline')+'" onclick="loadUsers('+x+')">'+x+'</button>';
      el.innerHTML=h;
    });
  }
  window.loadUsers=load;

  document.querySelectorAll('.role-tab').forEach(function(t){
    t.addEventListener('click',function(){
      document.querySelectorAll('.role-tab').forEach(function(x){x.classList.remove('active');});
      this.classList.add('active');currentRole=this.dataset.role;load(1);
    });
  });
  document.getElementById('userSearch').addEventListener('input',utils.debounce(function(){load(1);},400));

  window.openAddUser=function(){
    document.getElementById('userModalTitle').textContent='Add User';
    document.getElementById('editUserId').value='';
    document.getElementById('uName').value='';document.getElementById('uEmail').value='';
    document.getElementById('uPassword').value='';document.getElementById('uPhone').value='';
    document.getElementById('uRole').value='patient';
    document.getElementById('pwdGroup').style.display='';
    openModal('userModal');
  };

  window.openEditUser=function(u){
    document.getElementById('userModalTitle').textContent='Edit User';
    document.getElementById('editUserId').value=u.id;
    document.getElementById('uName').value=u.full_name;
    document.getElementById('uEmail').value=u.email;
    document.getElementById('uPassword').value='';
    document.getElementById('uPhone').value=u.phone||'';
    document.getElementById('uRole').value=u.role;
    document.getElementById('pwdGroup').style.display='none';
    openModal('userModal');
  };

  document.getElementById('submitUser').addEventListener('click',function(){
    var id=document.getElementById('editUserId').value;
    var payload={
      full_name:document.getElementById('uName').value.trim(),
      email:document.getElementById('uEmail').value.trim(),
      role:document.getElementById('uRole').value,
      phone:document.getElementById('uPhone').value.trim()
    };
    if(!id){
      payload.password=document.getElementById('uPassword').value;
      if(!payload.password||payload.password.length<8){utils.showAlert('Password must be 8+ chars.','warning');return;}
    }
    var url=id?utils.apiUrl('users/update.php'):utils.apiUrl('users/create.php');
    if(id) payload.user_id=parseInt(id);
    var btn=this;btn.disabled=true;
    utils.apiPost(url,payload,function(e,d){
      btn.disabled=false;
      if(d&&d.success){closeModal('userModal');utils.showToast(id?'Updated!':'Created!','success');load(currentPage);}
      else utils.showAlert(d?d.message:'Failed.','error');
    });
  });

  window.toggleUser=function(id,active){
    utils.confirmAction(active?'Activate this user?':'Deactivate this user?',function(){
      utils.apiPost(utils.apiUrl('users/toggle-status.php'),{user_id:id,is_active:active},function(e,d){
        if(d&&d.success){utils.showToast('Done!','success');load(currentPage);}
        else utils.showAlert(d?d.message:'Failed.','error');
      });
    });
  };

  load(1);
})();
</script>
<?php require_once ROOT_PATH . '/includes/footer.php'; ?>