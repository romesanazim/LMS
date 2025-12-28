@extends('layouts.app')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <div>
        <h2 class="mb-0">Manage Students</h2>
        <p class="text-muted mb-0">Add student with photo or send them direct emails.</p>
    </div>
    <button class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#studentCreateModal">Add / Enroll Student</button>
</div>

<div id="pageError" class="alert alert-warning d-none"></div>

<div class="card shadow-sm">
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table-striped align-middle">
                <thead>
                    <tr>
                        <th>Photo</th>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Roll #</th>
                        <th>Program</th>
                        <th>Status</th>
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody id="studentsTbody">
                    <tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="studentCreateModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Add / Enroll Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="createMsg" class="alert d-none"></div>
        <form id="createStudentForm" enctype="multipart/form-data">
          <div class="row g-3">
            <div class="col-md-12">
              <label class="form-label">Profile Picture</label>
              <input type="file" class="form-control" id="s_image" accept="image/*">
            </div>
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" id="s_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="s_email" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Password</label>
              <input type="password" class="form-control" id="s_password" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Roll Number</label>
              <input class="form-control" id="s_roll">
            </div>
            <div class="col-md-6">
              <label class="form-label">Program</label>
              <input class="form-control" id="s_program" placeholder="BS/BCS/MS">
            </div>
            <div class="col-md-6">
              <label class="form-label">Batch</label>
              <input class="form-control" id="s_batch" placeholder="2025">
            </div>
          </div>
          <div class="mt-3">
            <button class="btn btn-danger" type="submit">Create Student</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="emailModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Send Email to <span id="mailStudentName"></span></h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="mailMsg" class="alert d-none"></div>
        <input type="hidden" id="mail_student_id">
        <div class="mb-3">
          <label class="form-label">Subject</label>
          <input type="text" class="form-control" id="mail_subject" required>
        </div>
        <div class="mb-3">
          <label class="form-label">Message</label>
          <textarea class="form-control" id="mail_body" rows="4" required></textarea>
        </div>
        <button class="btn btn-primary w-100" onclick="sendEmail()">Send Now</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="studentEditModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog modal-lg">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Update Student</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="editMsg" class="alert d-none"></div>
        <form id="editStudentForm">
          <input type="hidden" id="e_id">
          <div class="row g-3">
            <div class="col-md-12">
               <label class="form-label">Update Photo (Leave blank to keep current)</label>
               <input type="file" class="form-control" id="e_image" accept="image/*">
            </div>
            <div class="col-md-6">
              <label class="form-label">Name</label>
              <input class="form-control" id="e_name" required>
            </div>
            <div class="col-md-6">
              <label class="form-label">Email</label>
              <input type="email" class="form-control" id="e_email" required>
            </div>
            </div>
          <div class="mt-3">
            <button class="btn btn-danger" type="submit">Save Changes</button>
          </div>
        </form>
      </div>
    </div>
  </div>
</div>
@endsection

@section('scripts')
<script>
  const pageError = document.getElementById('pageError');
  const tbody = document.getElementById('studentsTbody');

  function ensureAdmin() {
    const user = getUserData();
    if (!getAuthToken() || !user) { window.location.href = '/login'; return false; }
    if (user.role !== 'admin') { window.location.href = user.role === 'teacher' ? '/teacher/dashboard' : '/student/dashboard'; return false; }
    return true;
  }

  function setAlert(el, type, msg) {
    el.textContent = msg;
    el.className = 'alert alert-' + type;
    el.classList.remove('d-none');
  }

  function escapeHtml(str) {
    return String(str ?? '').replace(/[&<>"]+/g, s => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;'}[s] || s));
  }

  function renderRow(s) {
    const status = s.is_active ? '<span class="badge bg-success">Active</span>' : '<span class="badge bg-secondary">Inactive</span>';
    // Handle Profile Picture Path
    const imgPath = s.profile_picture ? `/storage/${s.profile_picture}` : 'https://ui-avatars.com/api/?name=' + s.name;
    
    return `
      <tr>
        <td><img src="${imgPath}" class="rounded-circle shadow-sm" style="width: 40px; height: 40px; object-fit: cover;"></td>
        <td>${s.id}</td>
        <td>${escapeHtml(s.name)}</td>
        <td>${escapeHtml(s.email)}</td>
        <td>${escapeHtml(s.roll_number)}</td>
        <td>${escapeHtml(s.program)}</td>
        <td>${status}</td>
        <td class="text-end">
          <button class="btn btn-sm btn-outline-primary me-1" onclick="openEmailModal(${s.id}, '${escapeHtml(s.name)}')"><i class="bi bi-envelope"></i> Mail</button>
          <button class="btn btn-sm btn-outline-danger me-1" onclick='openEdit(${JSON.stringify(s)})'>Edit</button>
          <button class="btn btn-sm btn-danger" onclick="deleteStudent(${s.id})">Delete</button>
        </td>
      </tr>
    `;
  }

  function loadStudents() {
    if (!ensureAdmin()) return;
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>';
    apiFetch('/api/admin/students')
      .then(r => r.json())
      .then(data => {
        if (!data || data.status !== true) throw new Error('Failed');
        tbody.innerHTML = data.data.length ? data.data.map(renderRow).join('') : '<tr><td colspan="8" class="text-center">No students.</td></tr>';
      })
      .catch(e => {
        setAlert(pageError, 'danger', 'Could not load students.');
      });
  }

  // --- CREATE WITH IMAGE ---
  document.getElementById('createStudentForm').addEventListener('submit', function(e) {
    e.preventDefault();
    const msg = document.getElementById('createMsg');
    msg.classList.add('d-none');

    // Use FormData instead of JSON for file uploads
    const formData = new FormData();
    formData.append('name', document.getElementById('s_name').value);
    formData.append('email', document.getElementById('s_email').value);
    formData.append('password', document.getElementById('s_password').value);
    formData.append('roll_number', document.getElementById('s_roll').value);
    formData.append('program', document.getElementById('s_program').value);
    formData.append('batch', document.getElementById('s_batch').value);
    
    const fileInput = document.getElementById('s_image');
    if(fileInput.files[0]) formData.append('profile_picture', fileInput.files[0]);

    // Note: apiFetch usually adds Content-Type: application/json. 
    // For FormData, the browser must set the boundary, so we send it differently.
    fetch('/api/admin/students', {
      method: 'POST',
      headers: { 'Authorization': 'Bearer ' + getAuthToken() }, // apiFetch helper logic
      body: formData
    })
    .then(r => r.json())
    .then(body => {
      if (body.status !== true) throw new Error(body.message);
      setAlert(msg, 'success', 'Student created with photo');
      loadStudents();
      this.reset();
    })
    .catch(err => setAlert(msg, 'danger', err.message));
  });

  // --- EMAIL FUNCTIONALITY ---
  function openEmailModal(id, name) {
    document.getElementById('mail_student_id').value = id;
    document.getElementById('mailStudentName').textContent = name;
    document.getElementById('mailMsg').classList.add('d-none');
    new bootstrap.Modal(document.getElementById('emailModal')).show();
  }

  async function sendEmail() {
    const btn = event.target;
    btn.disabled = true;
    const payload = {
      student_id: document.getElementById('mail_student_id').value,
      subject: document.getElementById('mail_subject').value,
      message: document.getElementById('mail_body').value
    };

    try {
      const res = await apiFetch('/api/admin/send-email', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(payload)
      });
      const data = await res.json();
      if (data.status) {
        setAlert(document.getElementById('mailMsg'), 'success', 'Email sent!');
        setTimeout(() => bootstrap.Modal.getInstance(document.getElementById('emailModal')).hide(), 1500);
      } else {
        throw new Error(data.message);
      }
    } catch (e) {
      setAlert(document.getElementById('mailMsg'), 'danger', 'Failed to send.');
    } finally {
      btn.disabled = false;
    }
  }

  function deleteStudent(id) {
    if (confirm('Delete permanently?')) {
      apiFetch(`/api/admin/students/${id}`, { method: 'DELETE' }).then(() => loadStudents());
    }
  }

  document.addEventListener('DOMContentLoaded', loadStudents);
</script>
@endsection