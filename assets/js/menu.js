(function(){
  const btnMenu=document.getElementById('btnMenu');
  const btnClose=document.getElementById('btnCloseSheet');
  const sheet=document.getElementById('sheet');
  const overlay=document.getElementById('overlay');
  function openSheet(){ sheet?.classList.add('show'); overlay?.classList.add('show'); document.body.style.overflow='hidden'; }
  function closeSheet(){ sheet?.classList.remove('show'); overlay?.classList.remove('show'); document.body.style.overflow=''; }
  btnMenu?.addEventListener('click',openSheet);
  btnClose?.addEventListener('click',closeSheet);
  overlay?.addEventListener('click',closeSheet);
})();
