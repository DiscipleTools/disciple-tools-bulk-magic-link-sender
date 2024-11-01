/**
 * Load a post by ID into the detail panel
 * @param id
 */
function loadPostDetail(id) {
  const item = listItems.get(id.toString());

  const detailTitle = document.getElementById('detail-title');
  const detailTemplate = document.getElementById('post-detail-template').content;
  const detailComments = document.getElementById('comments-detail-template').content;
  const detailContainer = document.getElementById('detail-content');
  const commentContainer = document.getElementById('detail-comments');

  // Set detail title
  detailTitle.innerText = item.name;

  let payload = {
    action: 'get',
    parts: jsObject.parts,
    sys_type: jsObject.sys_type,
    post_id: id,
    post_type: jsObject.template.record_type,
    comment_count: 2,
  }

  // Load detail comments
      //no route found, unsure where i got this from
        //const commentURL = jsObject.root + jsObject.parts.root + '/v1/' + jsObject.template.record_type + '/post';
          //http://localhost:10007/wp-json/templates/v1/contacts/post

      //looking for 'comment' to write/add a new one
        //const commentURL = jsObject.root + 'dt-posts/v2/' + jsObject.template.record_type + '/' + id; + '/comments';

      //same as /update api request, access denied
  const commentURL = jsObject.root + jsObject.parts.root + '/v1/' + jsObject.parts.type + '/post';
      //build url from where i found the post in the big json string, access denied
        //const commentURL = jsObject.root + 'templates/v1/1724093548/post';//jsObject.parts.root + '/v1/' + jsObject.template.record_type + '/post';
  
  console.log('URL:'+commentURL);
  fetch(commentURL,{
    method: "POST", // *GET, POST, PUT, DELETE, etc.
    headers: {
      "Content-Type": "application/json; charset=utf-8",
      "X-WP-Nonce": jsObject.nonce,
    },
    body: JSON.stringify(payload), // body data type must match "Content-Type" header
  })
    .then((response) => {
      console.log(response);
      //console.log(response.json());
      return response.json();
    })
    .then((json) => {
      const commentContent = detailComments.cloneNode(true); //clone template that holds divs & spans with formatting
      jQuery.each(json['comments']['comments'], function(i, val) { //for each comment in our json,

        //const div = commentContent.getElementById('dt-comment-name'); //get the div from the template
        const div = document.createElement("div");
        div.innerText = val['comment_author'] + ' @ ' + val['comment_date'];
        //const divContent = document.createTextNode(val['comment_author'] + ' @ ' + val['comment_date']); //add author text
        //div.appendChild(divContent);

        //const span = commentContent.getElementById('dt-comment-content'); //get the span from the template
        const span = document.createElement("span");
        span.innerText = val['comment_content'];
        //const spanContent = document.createTextNode(val['comment_content']); //add comment body text
        //span.appendChild(spanContent);
        //td.appendChild(span);

        commentContent.appendChild(div);
        commentContent.appendChild(span); //append both to the empty all-comments tile
      });

      commentContainer.replaceChildren(commentContent); //replace empty detail-comments div with our comment tile
    })
    .catch((reason) => {
      console.log(reason);
    });

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
      if (el.name == "comments") {
        let promises = [];
        promises.push(
          window.API.post_comment(
            jsObject.template.record_type,
            id,
            el.value,
            "comment",
          ).catch((err) => {
            console.error(err);
          }),
        );
        Promise.all(promises).then(function (responses) {
          done(responses);
        });
      }
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