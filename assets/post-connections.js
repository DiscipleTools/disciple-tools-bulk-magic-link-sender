function loadPostDetail(id) {
  const item = listItems.get(id.toString());

  const detailTitle = document.getElementById('detail-title');
  const detailTemplate = document.getElementById('post-detail-template');
  const loadingTemplate = document.getElementById('post-loading-template');
  const detailContent = document.getElementById('detail-content');

  detailContent.replaceChildren(loadingTemplate.content.cloneNode(true));

  detailTitle.innerText = item.name;
  const content = detailTemplate.content.cloneNode(true);
  // const fields = jsObject.fieldSettings;
  const fields = jsObject.template.fields;
  for (const field of jsObject.template.fields) {
    console.log(field);
    const input = content.querySelector(`[name="${field.id}"]`);
    if (input) {
      input.value = item[field.id];
    }
  }

  detailContent.replaceChildren(content);

  document.getElementById('list').classList.remove('is-expanded');
}
function togglePanels() {
  document.querySelectorAll('#list, #detail').forEach((el) => {
    el.classList.toggle('is-expanded');
  })
}

function toggleFilters() {
  document.querySelectorAll('.filters').forEach((el) => {
    el.classList.toggle('hidden');
  })
}

document.querySelectorAll('button.filter').forEach((el) => el.addEventListener('click', toggleFilters));
