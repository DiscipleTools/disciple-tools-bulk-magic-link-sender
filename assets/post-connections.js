function loadPostDetail(id) {
  const item = listItems.get(id.toString());

  const detailTitle = document.getElementById('detail-title');
  const detailTemplate = document.getElementById('post-detail-template');
  const detailContent = document.getElementById('detail-content');

  detailTitle.innerText = item.name;
  const content = detailTemplate.content.cloneNode(true);

  // set value of all inputs in the template
  setInputValues(content, item);

  detailContent.replaceChildren(content);

  document.getElementById('list').classList.remove('is-expanded');
}

function setInputValues(parent, post) {
  const elements = parent.childNodes;

  for (const element of elements) {
    if (!element.tagName) {
      continue;
    }
    const tagName = element.tagName.toLowerCase();
    const name = element.attributes.name ? element.attributes.name.value : null;

    const postValue = post[name];

    switch (tagName) {
      case 'dt-date':
        const date = new Date(post[name].timestamp*1000);
        element.value = date.toISOString().substring(0, 10);
        break;
      case 'dt-single-select':
        element.value = postValue?.key;
        break;
      case 'dt-tile':
        setInputValues(element, post);
        break;
      default:
        if (tagName.startsWith('dt-')) {
          element.value = post[name];
        }
    }
  }
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
