/**
 * Load a post by ID into the detail panel
 * @param id
 */
function loadPostDetail(id) {

  const detailTitle = document.getElementById('detail-title');
  const detailPostId = document.getElementById('detail-title-post-id');
  const detailTemplate = document.getElementById('post-detail-template').content;
  const detailContainer = document.getElementById('detail-content');

  // clone detail template
  const content = detailTemplate.cloneNode(true);
  const commentTile = content.getElementById('comments-tile');

  const postLoadEventDetail = { id };

  if (id > 0) {
    const item = listItems.get(id.toString());
    postLoadEventDetail.post = item;

    // Set detail title
    detailTitle.innerText = item.name;
    detailPostId.innerText = `(#${item.ID})`;

    // set value of all inputs in the template
    content.getElementById('post-id').value = id;
    setInputValues(content, item);

    const button = content.getElementById('comment-button');
    button.addEventListener('click', () => {
      submitComment(id);
    });
    setComments(commentTile, item.ID);
  } else {
    detailTitle.innerText = jsObject.translations.new_record;
    detailPostId.innerText = ``;

    content.getElementById('post-id').value = id;

    // hide comment container
    commentTile.style.display = 'none';
  }

  // insert templated content into detail panel
  detailContainer.replaceChildren(content);
  detailContainer.dispatchEvent(new CustomEvent('dt:post-load', {
    detail: postLoadEventDetail,
  }));

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
function loadListItems(posts) {
  if ( (!jsObject.items || !jsObject.items.posts) && !posts ) {
    return;
  }

  if (!posts) {
    posts = jsObject.items.posts;
  }

  const resultCount = document.getElementById('results-count-number');
  resultCount.innerText = posts.length;

  const itemList = document.getElementById('list-items');
  itemList.replaceChildren([]);
  const itemTemplate = document.getElementById('list-item-template').content;

  for (const item of posts) {
    const itemEl = itemTemplate.cloneNode(true);
    itemEl.querySelector('li').id = `item-${item.ID}`;
    populateListItemTemplate(itemEl, item);
    itemList.append(itemEl);
    if (!listItems.has(item.ID.toString())) {
      listItems.set(item.ID.toString(), item);
    }

  }

}

function populateListItemTemplate(itemEl, item) {
  const link = itemEl.querySelector('a');
  link.href = `javascript:loadPostDetail(${item.ID})`;

  itemEl.querySelector('.post-id').innerText = `(#${item.ID})`;
  itemEl.querySelector('.post-title').innerText = item.name;
  itemEl.querySelector('.post-updated-date').innerText = window.SHAREDFUNCTIONS.formatDate(
    item.last_modified?.timestamp
  );

  if (item.meta) {
    itemEl.querySelector('.post-meta').innerText = item.meta;
  }
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
      case 'dt-connection':
      case 'dt-users-connection':
        element.value = DtWebComponents.ComponentService.convertApiValue(tagName, postValue);
        break;
      case 'dt-location':
        element.value = postValue?.map(val => ({
          ...val,
          id: val.id.toString(),
        }));
        break;
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
    if (el.localName.startsWith('dt-') && el.name) {
      data.el[el.name] = el.value;
    }
  });
  console.log(data);

  const id = formdata.get('id');
  submitComment(id);
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

    const value = DtWebComponents.ComponentService.convertValue(el.localName, el.value);
    const fieldType = type === 'custom' ? 'custom' : 'dt';
    payload['fields'][fieldType].push({
      id: field_id,
      type,
      value: value,
    });
  });

  const url = window.apiRoot + '/update';
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
        } else {
          jsObject.items.posts.splice(0, 0, json.post);
        }
        listItems.set(json.post.ID.toString(), json.post);

        if (id === "0") {
          searchData();
        } else {
          // update list item
          const itemEl = document.getElementById(`item-${json.post.ID}`);
          populateListItemTemplate(itemEl, json.post);
        }

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

function clearSearch() {
  const id = jsObject.post.ID;
  document.getElementById('search').value = '';
  searchData(id);
}

function toggleFilters() {
  document.querySelectorAll('.filters').forEach((el) => {
    el.classList.toggle('hidden');
  })
}

const searchData = () => {
  const id = jsObject.post.ID;
  const text = document.getElementById('search').value;
  let clear_button = document.getElementById('clear-button');
  if (!text && clear_button.style.display == 'block'){
    clear_button.setAttribute('style', 'display: none;');
  }else if (text && clear_button.style.display == 'none'){
    clear_button.setAttribute('style', 'display: block;');
  }
  let payload = {
    action: 'get',
    parts: jsObject.parts,
    sys_type: jsObject.sys_type,
    post_id: id,
    post_type: jsObject.template.record_type,
    text: text,
    sort: document.querySelector('input[name="sort"]:checked').value,
    fields: {},
  }

  // Get filter values
  const filterContainer = document.querySelector('.filters .container');
  const filterComponents = Array.from(filterContainer.children).filter(el =>
    el.tagName.toLowerCase().startsWith('dt-')
  );
  for(const el of filterComponents) {
    switch (el.localName) {
      case 'dt-multi-select':
      case 'dt-multi-select-button-group':
        payload.fields[el.name] = (el.value || []).filter(x => !x.startsWith('-'));
        break;
      default:
        payload.fields[el.name] = DtWebComponents.ComponentService.convertValue(el.localName, el.value)
        break;
    }
  }

  let temp_spinner = document.getElementById('temp-spinner');
  temp_spinner.setAttribute('class', 'loading-spinner active');

  const url = window.apiRoot + '/sort_post';

  fetch(url,{
    method: "POST",
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "X-WP-Nonce": jsObject.nonce,
    },
    body: JSON.stringify(payload),
  })
    .then((response) => {

      return response.json();

    }).then((json) => {

      temp_spinner.setAttribute('class', 'loading-spinner inactive');

      loadListItems(json['posts']);

    })
    .catch((reason) => {
      console.log("reason:");
      console.log(reason);
    });
}

const searchChange = debounce(searchData);

function debounce(callback) {
  let delay = 1000
  let timer
  return function(...args) {
    clearTimeout(timer)
    timer = setTimeout(() => {
      callback(...args);
    }, delay)
  }
}

function assignLanguage(lang) {
  window.location.assign('?lang=' + lang);
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

  const commentURL = window.apiRoot + '/post';
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
      // console.log(response);
      return response.json();
    })
    .then((json) => {
      for (const val of json['comments']['comments']) {
        if (!val['comment_content']) {
          continue;
        }
        const actionBlock = document.createElement('div');
        actionBlock.className = "action-block";

        const activityBlock = document.createElement("div");
        activityBlock.className = "activity-block";

        const commentHeaderTemplate = document.getElementById('comment-header-template').content;
        const commentHeader = commentHeaderTemplate.cloneNode(true);
        const commentAuthor = commentHeader.getElementById('comment-author');
        const commentDate = commentHeader.getElementById('comment-date');

        commentAuthor.innerText = val['comment_author'];
        const commentDateTime = window.moment(val.comment_date_gmt + 'Z');
        commentDate.innerText = window.SHAREDFUNCTIONS.formatDate(
          moment(commentDateTime).unix(),
          true,
        );

        const commentContentTemplate = document.getElementById('comment-content-template').content;
        const commentContent = commentContentTemplate.cloneNode(true);
        const commentId = commentContent.getElementById('comment-id');
        const commentText = commentContent.getElementById('comment-content');

        commentId.className = "comment-bubble " + val['comment_ID'];
        commentId.setAttribute("data-comment-id", val['comment_ID']);
        commentText.setAttribute("title", val['comment_date']);
        const decoder = document.createElement('div');
        decoder.innerHTML = val['comment_content'];
        commentText.innerText = decoder.textContent;

        activityBlock.appendChild(commentHeader);
        activityBlock.appendChild(commentContent);

        commentsTile.appendChild(actionBlock);
        commentsTile.appendChild(activityBlock);
      }


    })
    .catch((reason) => {
      console.log(reason);
    });
}

function submitComment(id) {

  const textArea = document.getElementById('comments-text-area');
  if (!textArea.value) {
    return false;
  }

  let payload = {
    action: 'post',
    parts: jsObject.parts,
    sys_type: jsObject.sys_type,
    post_id: id,
    post_type: jsObject.template.record_type,
    comment: textArea.value,
  }

  const url = window.apiRoot + '/comment';

  fetch(url,{
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

function attachFilterListeners() {
  const filterContainer = document.querySelector('.filters .container');
  if (!filterContainer) return;


  const filterComponents = Array.from(filterContainer.children).filter(el =>
    el.tagName.toLowerCase().startsWith('dt-')
  );
  filterComponents.forEach(element => {
    element.addEventListener('change', searchChange);
  });
}

document.addEventListener('DOMContentLoaded', attachFilterListeners);

window.addEventListener('load', () => {
  const apiVersion = jsObject.parts.version ?? 'v1';
  window.apiRoot = jsObject.root + jsObject.parts.root + '/' + apiVersion + '/' + jsObject.parts.type;
});
