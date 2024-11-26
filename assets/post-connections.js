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

  const button = content.getElementById('comment-button');
  button.addEventListener('click', () => {
    submitComment(id);
  });
  const commentTile = content.getElementById('comments-tile');
  setComments(commentTile, item.ID);

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
        if (postValue && postValue.timestamp) {
          const date = new Date(postValue.timestamp * 1000);
          element.value = date.toISOString().substring(0, 10);
        }
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

  const form = event.target.closest('form');
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
    const type = el.dataset.type;

    // const value = DtWebComponents.ComponentService.convertValue(el.localName, el.value);
    const value = window.WebComponentServices.ComponentService.convertValue(el.localName, el.value);
    const fieldType = type === 'custom' ? 'custom' : 'dt';
    payload['fields'][fieldType].push({
      id: field_id,
      type,
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
        showNotification('Item saved', 'success');

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

/**
 * Insert new notification message into snackbar area
 * @param message - Content of message
 * @param type - CSS class to add (e.g. succeess, error)
 * @param duration - Duration (ms) to keep message visible
 */
function showNotification(message, type, duration = 5000) {
  const template = document.getElementById('snackbar-item-template').content;
  const newItem = template.cloneNode(true);
  const now = Date.now()
  const itemEl = newItem.querySelector('.snackbar-item');

  if (type) {
    itemEl.classList.add(type);
  }
  itemEl.innerText = message;
  const elId = `snack-${now}`
  itemEl.id = elId;
  document.getElementById('snackbar-area').appendChild(newItem);

  setTimeout(async () => {
    const el = document.getElementById(elId);

    // wait for CSS transition
    el.classList.add('exiting');
    await new Promise(r => setTimeout(r, 500));

    // remove element from DOM
    el.remove();
  }, duration);
}

function togglePanels() {
  document.querySelectorAll('#list').forEach((el) => {
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

function setComments(commentsTile, id) {
  let payload = {
    action: 'get',
    parts: jsObject.parts,
    sys_type: jsObject.sys_type,
    post_id: id,
    post_type: jsObject.template.record_type,
    comment_count: 2,
  }

  const commentURL = jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/post';
  const comments = commentsTile.querySelectorAll('.activity-block, .action-block');
  if (comments.length) {
    for (const comment of comments) {
      comment.parentNode.removeChild(comment);
    }
  }
  fetch(commentURL,{
    method: "POST",
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "X-WP-Nonce": jsObject.nonce,
    },
    body: JSON.stringify(payload),
  })
    .then((response) => {
      console.log(response);
      return response.json();
    })
    .then((json) => {
      const actionBlock = document.createElement('div');
      actionBlock.className = "action-block";

      const activityBlock = document.createElement("div");
      activityBlock.className = "activity-block";

      for (const val of json['comments']['comments']) {
        const commentHeaderTemplate = document.getElementById('comment-header-template').content;
        const commentHeader = commentHeaderTemplate.cloneNode(true);
        const commentAuthor = commentHeader.getElementById('comment-author');
        const commentDate = commentHeader.getElementById('comment-date');

        commentAuthor.innerText = val['comment_author'];
        commentDate.innerText = val['comment_date'];

        const commentContentTemplate = document.getElementById('comment-content-template').content;
        const commentContent = commentContentTemplate.cloneNode(true);
        const commentId = commentContent.getElementById('comment-id');
        const commentText = commentContent.getElementById('comment-content');

        commentId.className = "comment-bubble " + val['comment_ID'];
        commentId.setAttribute("data-comment-id", val['comment_ID']);
        commentText.setAttribute("title", val['comment_date']);
        commentText.innerText = val['comment_content'];

        activityBlock.appendChild(commentHeader);
        activityBlock.appendChild(commentContent);
      }

      commentsTile.appendChild(actionBlock);
      commentsTile.appendChild(activityBlock);


    })
    .catch((reason) => {
      console.log(reason);
    });
}

  function submitComment(id) {

    const textArea = document.getElementById('comments-text-area');


    let payload = {
      action: 'post',
      parts: jsObject.parts,
      sys_type: jsObject.sys_type,
      post_id: id,
      post_type: jsObject.template.record_type,
      comment: textArea.value,
    }

    const commentURL = jsObject.root + 'dt-posts/v2/' + jsObject.template.record_type + '/' + id + '/comments';

    fetch(commentURL,{
      method: "POST",
      headers: {
        "Content-Type": "application/json; charset=utf-8",
        "X-WP-Nonce": jsObject.nonce,
      },
      body: JSON.stringify(payload),
    })
      .then((response) => {
        textArea.value = '';
        const commentTile = document.getElementById('comments-tile');
        setComments(commentTile, id);
        return response.json();
      })
      .catch((reason) => {
        console.log("reason:");
        console.log(reason);
      });
  }
