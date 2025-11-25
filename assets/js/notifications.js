document.addEventListener('click', (e)=>{
  const btn = e.target.closest('.mdw-alert__close');
  if(btn){
    const alert = btn.closest('.mdw-alert');
    alert?.remove();
  }
});