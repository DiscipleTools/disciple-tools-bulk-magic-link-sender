function togglePanels() {
  document.querySelectorAll('#list, #detail').forEach((el) => {
    el.classList.toggle('is-expanded');
  })
}

document.querySelectorAll('.details-toggle').forEach((el) => el.addEventListener('click', togglePanels));

function toggleFilters() {
  document.querySelectorAll('.filters').forEach((el) => {
    el.classList.toggle('hidden');
  })
}

document.querySelectorAll('button.filter').forEach((el) => el.addEventListener('click', toggleFilters));
