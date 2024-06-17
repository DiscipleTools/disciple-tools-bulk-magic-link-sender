/**
 * Load a post by ID into the detail panel
 * @param id
 */
function loadPostDetail(id) {
  const item = listItems.get(id.toString());

  const detailTitle = document.getElementById('detail-title');
  const detailTemplate = document.getElementById('post-detail-template').content;
  const detailContainer = document.getElementById('detail-content');

  // Set detail title
  detailTitle.innerText = item.name;

  // clone detail template
  const content = detailTemplate.cloneNode(true);

  // set value of all inputs in the template
  content.getElementById('post-id').value = id;
  setInputValues(content, item);

  // insert templated content into detail panel
  detailContainer.replaceChildren(content);

  // open detail panel
  document.getElementById('list').classList.remove('is-expanded');

  // Set active class in the list
  document.querySelectorAll('#list .items li.active').forEach((el) => {
    el.classList.remove('active');
  });
  const listItem = document.getElementById(`item-${id}`);
  if (listItem) {
    listItem.classList.add('active');
  }
}

/**
 * Load the list items into the UI from the jsObject.items property
 */
function loadListItems() {
  if ( !jsObject.items || !jsObject.items.posts ) {
    return;
  }

  const itemList = document.getElementById('list-items');
  itemList.replaceChildren([]);
  const itemTemplate = document.getElementById('list-item-template').content;

  for (const item of jsObject.items.posts) {
    const itemEl = itemTemplate.cloneNode(true);
    itemEl.querySelector('li').id = `item-${item.ID}`;
    populateListItemTemplate(itemEl, item);
    itemList.append(itemEl);
  }
}

function populateListItemTemplate(itemEl, item) {
  const link = itemEl.querySelector('a');
  link.href = `javascript:loadPostDetail(${item.ID})`;
  link.innerText = item.name;
}

/**
 * Set the values of all dt-* components within a container
 * @param {Element} parent
 * @param {Object} post
 */
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

/**
 * Submit event for saving detail form
 * @param {Event} event
 */
function saveItem(event) {
  event.preventDefault();
  console.log(event);

  const form = event.target;
  const formdata = new FormData(form);
  console.log(formdata);

  const data = {
    form: {},
    el: {},
  };
  formdata.forEach((value, key) => (data.form[key] = value));
  Array.from(form.elements).forEach((el) => {
    if (el.localName.startsWith('dt-')) {
      data.el[el.name] = el.value;
    }
  });
  console.log(data);

  const id = formdata.get('id');
  let payload = {
    action: 'get',
    parts: jsObject.parts,
    sys_type: jsObject.sys_type,
    post_id: id,
    post_type: jsObject.template.record_type,
    fields: {
      dt: [],
      custom: [],
    },
  }

  Array.from(form.elements).forEach((el) => {
    if (!el.localName.startsWith('dt-')) {
      return;
    }
    // if readonly: skip
    if (el.disabled) {
      return;
    }
    const field_id = el.name;


    const value = WebComponentServices.ComponentService.convertValue(el.localName, el.value);
    const fieldType = el.classList.contains('custom-field') ? 'custom' : 'dt';
    payload['fields'][fieldType].push({
      id: field_id,
      // type: field_type,
      value: value,
    });
  });

  const url = jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/update';
  fetch(url,{
    method: "POST", // *GET, POST, PUT, DELETE, etc.
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "X-WP-Nonce": jsObject.nonce,
    },
    body: JSON.stringify(payload), // body data type must match "Content-Type" header
  })
    .then((response) => {
      console.log(response);
      return response.json();
    })
    .then((json) => {
      if (json.success && json.post) {
        // update jsObject
        const idx = jsObject.items.posts.findIndex((i) => i.ID === json.post.ID);
        if (idx > -1) {
          jsObject.items.posts[idx] = json.post;
        }
        listItems.set(json.post.ID.toString(), json.post);

        // update list item
        const itemEl = document.getElementById(`item-${json.post.ID}`);
        populateListItemTemplate(itemEl, json.post);

        // go back to list
        togglePanels();
      }
    })
    .catch((reason) => {
      console.log(reason);
    });
}
function togglePanels() {
  document.querySelectorAll('#list, #detail').forEach((el) => {
    el.classList.toggle('is-expanded');
  });

  // clear active classes on list
  document.querySelectorAll('#list .items li.active').forEach((el) => {
    el.classList.remove('active');
  })
}

function toggleFilters() {
  document.querySelectorAll('.filters').forEach((el) => {
    el.classList.toggle('hidden');
  })
}

document.querySelectorAll('button.filter').forEach((el) => el.addEventListener('click', toggleFilters));
