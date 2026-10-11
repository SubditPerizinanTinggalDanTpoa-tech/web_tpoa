(function(){
  const $=(s,r=document)=>r.querySelector(s), $$=(s,r=document)=>Array.from(r.querySelectorAll(s));
  const sidebar=$('#sidebar'), toggle=$('#menuToggle');
  if(toggle&&sidebar){toggle.addEventListener('click',()=>sidebar.classList.toggle('open'));document.addEventListener('click',e=>{if(window.innerWidth<=760&&sidebar.classList.contains('open')&&!sidebar.contains(e.target)&&!toggle.contains(e.target))sidebar.classList.remove('open')});}
  function toast(message){const node=$('#appToast'), body=$('#toastMessage');if(body)body.textContent=message;if(node&&window.bootstrap){bootstrap.Toast.getOrCreateInstance(node,{delay:4200}).show()}else{alert(message)}}
  const notif=$('#notificationButton');if(notif)notif.addEventListener('click',()=>toast('Panel notifikasi akan dihubungkan pada modul berikutnya.'));
  const profile=$('#profileButton');if(profile)profile.addEventListener('click',()=>toast('Profil demo: Ahmad Pratama — Ketua Tim (TPOA). Login dan hak akses belum diaktifkan.'));
  const registration=$('#registrationForm');
  if(registration){registration.addEventListener('submit',async function(e){
    e.preventDefault(); if(!registration.reportValidity())return;
    const submit=registration.querySelector('[type="submit"]'); const oldText=submit?submit.innerHTML:'';
    if(submit){submit.disabled=true;submit.innerHTML='<span class="spinner-border spinner-border-sm me-2"></span>Menyimpan...';}
    try{
      const response=await fetch('api/organisasi.php',{method:'POST',body:new FormData(registration),headers:{'Accept':'application/json'}});
      const result=await response.json();
      if(!response.ok||!result.ok)throw new Error(result.message||'Data gagal disimpan.');
      toast(result.message+' Nomor registrasi: '+result.data.nomor_registrasi);
      registration.reset(); const date=registration.querySelector('input[name="tanggal"]');if(date)date.value=new Date().toISOString().slice(0,10);
      setTimeout(()=>{window.location.href='?page='+((registration.querySelector('[name="jenis"]')?.value==='INGO')?'data_ingo':'data_yayasan')},900);
    }catch(err){toast(err.message||'Tidak dapat menghubungi server. Pastikan Apache dan MySQL berjalan.');}
    finally{if(submit){submit.disabled=false;submit.innerHTML=oldText;}}
  });}
  const search=$('#recordSearch'), status=$('#statusFilter'), type=$('#typeFilter'), table=$('#recordsTable');
  function filterRows(){if(!table)return;let visible=0;$$('tbody tr',table).forEach(row=>{const text=row.innerText.toLowerCase();const matchSearch=!search||text.includes(search.value.toLowerCase());const matchStatus=!status||!status.value||row.dataset.status===status.value;const matchType=!type||!type.value||row.dataset.type===type.value;const show=matchSearch&&matchStatus&&matchType;row.style.display=show?'':'none';if(show)visible++});const count=$('#resultCount');if(count)count.textContent=`Menampilkan ${visible} rekam yang cocok dari database`;}
  [search,status,type].forEach(el=>{if(el)el.addEventListener('input',filterRows)});
  const modal=$('#detailModal'), title=$('#detailTitle'), content=$('#detailContent'), detailStatus=$('#detailStatus');
  function closeModal(){if(modal){modal.classList.remove('open');modal.setAttribute('aria-hidden','true')}}
  function addDetailRow(label,value){const row=document.createElement('div');row.className='detail-row';const key=document.createElement('span');key.className='detail-key';key.textContent=label;const val=document.createElement('strong');val.className='detail-value';val.textContent=(value===null||value===undefined||value==='')?'—':String(value);row.append(key,val);content.append(row)}
  async function showDetail(id,name){if(title)title.textContent=name||'Detail organisasi';if(content)content.innerHTML='<p>Memuat detail dari database...</p>';if(modal){modal.classList.add('open');modal.setAttribute('aria-hidden','false')}if(detailStatus){detailStatus.className='status-pill status-process';detailStatus.textContent='Memuat...'}
    try{const response=await fetch('api/organisasi.php?id='+encodeURIComponent(id),{headers:{'Accept':'application/json'}});const result=await response.json();if(!response.ok||!result.ok)throw new Error(result.message||'Detail tidak dapat dimuat.');const d=result.data;if(title)title.textContent=d.nama_resmi;if(content){content.innerHTML='';addDetailRow('Nomor registrasi',d.nomor_registrasi);addDetailRow('Jenis organisasi',d.jenis==='YAYASAN_ASING'?'Yayasan Asing':'INGO');addDetailRow('Negara asal',d.negara_asal);addDetailRow('Tanggal pengajuan',d.tanggal_pengajuan);addDetailRow('Nama perwakilan',d.nama_perwakilan);addDetailRow('Email resmi',d.email_resmi);addDetailRow('Bidang kegiatan',d.bidang_kegiatan);addDetailRow('Alamat',d.alamat);const docs=document.createElement('div');docs.className='detail-documents';const h=document.createElement('strong');h.textContent='Dokumen pendukung';docs.append(h);if(d.dokumen&&d.dokumen.length){const list=document.createElement('ul');d.dokumen.forEach(doc=>{const li=document.createElement('li');li.textContent=doc.nama_dokumen+' ('+Math.max(1,Math.round((doc.ukuran_bytes||0)/1024))+' KB)';list.append(li)});docs.append(list)}else{const empty=document.createElement('p');empty.textContent='Belum ada dokumen terunggah.';docs.append(empty)}content.append(docs);}
      const labels={DRAFT:'Draft',DIAJUKAN:'Diajukan',DALAM_REVIEW:'Dalam Proses',PERLU_PERBAIKAN:'Perlu Perbaikan',DIVERIFIKASI:'Terverifikasi',DISETUJUI:'Disetujui',DITOLAK:'Ditolak'};const classes={DRAFT:'status-review',DIAJUKAN:'status-process',DALAM_REVIEW:'status-process',PERLU_PERBAIKAN:'status-review',DIVERIFIKASI:'status-approved',DISETUJUI:'status-approved',DITOLAK:'status-rejected'};if(detailStatus){detailStatus.className='status-pill '+(classes[d.status]||'status-process');detailStatus.textContent=labels[d.status]||d.status;}
    }catch(err){if(content)content.textContent=err.message||'Terjadi kesalahan saat mengambil detail.';if(detailStatus)detailStatus.textContent='Gagal memuat';}
  }
  $$('.detail-button').forEach(btn=>btn.addEventListener('click',()=>{const id=btn.dataset.id;if(id)showDetail(id,btn.dataset.name);else toast('ID organisasi tidak tersedia. Muat ulang halaman.')}));
  ['#closeDetail','#confirmDetail'].forEach(sel=>{const el=$(sel);if(el)el.addEventListener('click',closeModal)});if(modal)modal.addEventListener('click',e=>{if(e.target===modal)closeModal()});document.addEventListener('keydown',e=>{if(e.key==='Escape')closeModal()});
  const year=$('#yearFilter');if(year){year.addEventListener('change',()=>{const sets={2025:[4,3,6,5,7,4,10,11,12,16,19,24],2024:[3,5,4,7,8,6,9,10,13,15,18,22],2023:[2,3,5,4,6,7,8,9,11,12,15,18]};const vals=sets[year.value]||sets['2025'];$$('.bar-pair').forEach((pair,i)=>{const a=pair.querySelector('.bar-ingo');if(a)a.style.height=(vals[i]*4.3)+'px'})})}
})();
