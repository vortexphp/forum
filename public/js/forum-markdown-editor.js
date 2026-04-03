(function () {
  "use strict";

  var shortcodeEmojiMap = {
    ":smile:": "😄",
    ":thumbsup:": "👍",
    ":heart:": "❤️",
    ":fire:": "🔥",
    ":laugh:": "😂",
    ":sad:": "😢",
    ":wink:": "😉",
    ":rocket:": "🚀",
  };

  var classicEmojiMap = {
    ":-D": "😄",
    ":D": "😄",
    ":-P": "😛",
    ":P": "😛",
    ":-p": "😛",
    ":p": "😛",
    ":-)": "🙂",
    ":)": "🙂",
    ":-(": "🙁",
    ":(": "🙁",
    ";-)": "😉",
    ";)": "😉",
    ":'(": "😢",
    XD: "😂",
    xD: "😂",
    "<3": "❤️",
  };

  var toolbar = [
    { label: "B", title: "Bold (Ctrl+B)", action: "bold" },
    { label: "I", title: "Italic (Ctrl+I)", action: "italic" },
    { label: "H1", title: "Heading", action: "heading" },
    { label: "Quote", title: "Quote", action: "quote" },
    { label: "Code", title: "Inline code", action: "code" },
    { label: "Link", title: "Insert link (Ctrl+K)", action: "link" },
    { label: "List", title: "Bullet list", action: "list" },
    { label: "Emoji", title: "Insert :smile:", action: "emoji" },
    { label: "Mention", title: "Insert @username", action: "mention" },
    { label: "Preview", title: "Toggle preview", action: "preview" },
  ];

  function init() {
    var areas = document.querySelectorAll("textarea.js-markdown-editor");
    for (var i = 0; i < areas.length; i += 1) {
      enhance(areas[i]);
    }
  }

  function enhance(textarea) {
    if (textarea.dataset.editorReady === "1") {
      return;
    }
    textarea.dataset.editorReady = "1";

    var shell = document.createElement("div");
    shell.className = "forum-md-shell";
    textarea.parentNode.insertBefore(shell, textarea);
    shell.appendChild(textarea);

    var bar = document.createElement("div");
    bar.className = "forum-md-toolbar";
    shell.insertBefore(bar, textarea);

    var preview = document.createElement("div");
    preview.className = "forum-md-preview hidden";
    preview.setAttribute("aria-live", "polite");
    shell.appendChild(preview);

    for (var i = 0; i < toolbar.length; i += 1) {
      addButton(bar, textarea, preview, toolbar[i]);
    }

    textarea.addEventListener("keydown", function (event) {
      if (!(event.ctrlKey || event.metaKey)) return;
      var key = event.key.toLowerCase();
      if (key === "b") {
        event.preventDefault();
        surround(textarea, "**", "**");
      } else if (key === "i") {
        event.preventDefault();
        surround(textarea, "*", "*");
      } else if (key === "k") {
        event.preventDefault();
        link(textarea);
      }
    });

    textarea.addEventListener("input", function () {
      if (!preview.classList.contains("hidden")) {
        renderPreview(textarea, preview);
      }
    });
  }

  function addButton(bar, textarea, preview, spec) {
    var btn = document.createElement("button");
    btn.type = "button";
    btn.className = "forum-md-btn";
    btn.textContent = spec.label;
    btn.title = spec.title;
    btn.addEventListener("click", function () {
      runAction(spec.action, textarea, preview);
    });
    bar.appendChild(btn);
  }

  function runAction(action, textarea, preview) {
    switch (action) {
      case "bold":
        surround(textarea, "**", "**");
        break;
      case "italic":
        surround(textarea, "*", "*");
        break;
      case "heading":
        linePrefix(textarea, "# ");
        break;
      case "quote":
        linePrefix(textarea, "> ");
        break;
      case "code":
        surround(textarea, "`", "`");
        break;
      case "link":
        link(textarea);
        break;
      case "list":
        linePrefix(textarea, "- ");
        break;
      case "emoji":
        insert(textarea, " :smile: ");
        break;
      case "mention":
        insert(textarea, " @username ");
        break;
      case "preview":
        preview.classList.toggle("hidden");
        renderPreview(textarea, preview);
        break;
      default:
        break;
    }
    textarea.focus();
  }

  function surround(textarea, left, right) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var selected = textarea.value.slice(start, end);
    var output = left + selected + right;
    textarea.setRangeText(output, start, end, "end");
    if (selected.length === 0) {
      var pos = start + left.length;
      textarea.setSelectionRange(pos, pos);
    }
  }

  function linePrefix(textarea, prefix) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var block = textarea.value.slice(start, end);
    if (block.length === 0) {
      textarea.setRangeText(prefix, start, end, "end");
      return;
    }
    var out = block
      .split("\n")
      .map(function (line) {
        return prefix + line;
      })
      .join("\n");
    textarea.setRangeText(out, start, end, "select");
  }

  function insert(textarea, text) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    textarea.setRangeText(text, start, end, "end");
  }

  function link(textarea) {
    var start = textarea.selectionStart;
    var end = textarea.selectionEnd;
    var selected = textarea.value.slice(start, end) || "text";
    var output = "[" + selected + "](https://)";
    textarea.setRangeText(output, start, end, "end");
  }

  function renderPreview(textarea, preview) {
    var source = normalize(textarea.value);
    preview.innerHTML = markdownToHtml(source);
  }

  function normalize(raw) {
    var text = raw.trim();
    Object.keys(shortcodeEmojiMap).forEach(function (key) {
      text = text.split(key).join(shortcodeEmojiMap[key]);
    });
    Object.keys(classicEmojiMap).forEach(function (key) {
      var re = new RegExp("(^|\\s)" + escapeRegExp(key) + "(?=\\s|$)", "g");
      text = text.replace(re, "$1" + classicEmojiMap[key]);
    });
    text = text.replace(/\[b\](.*?)\[\/b\]/gi, "**$1**");
    text = text.replace(/\[i\](.*?)\[\/i\]/gi, "*$1*");
    text = text.replace(/\[s\](.*?)\[\/s\]/gi, "~~$1~~");
    text = text.replace(/\[code\](.*?)\[\/code\]/gi, "`$1`");
    text = text.replace(/\[quote\](.*?)\[\/quote\]/gi, "> $1");
    text = text.replace(/\[url=(https?:\/\/[^\]]+)\](.*?)\[\/url\]/gi, "[$2]($1)");
    return text;
  }

  function markdownToHtml(text) {
    var escaped = escapeHtml(text);
    var codeBlocks = [];
    escaped = escaped.replace(/```([\s\S]*?)```/g, function (_, code) {
      codeBlocks.push("<pre><code>" + code + "</code></pre>");
      return "%%CODEBLOCK" + (codeBlocks.length - 1) + "%%";
    });

    escaped = escaped.replace(/^###### (.*)$/gm, "<h6>$1</h6>");
    escaped = escaped.replace(/^##### (.*)$/gm, "<h5>$1</h5>");
    escaped = escaped.replace(/^#### (.*)$/gm, "<h4>$1</h4>");
    escaped = escaped.replace(/^### (.*)$/gm, "<h3>$1</h3>");
    escaped = escaped.replace(/^## (.*)$/gm, "<h2>$1</h2>");
    escaped = escaped.replace(/^# (.*)$/gm, "<h1>$1</h1>");
    escaped = escaped.replace(/^> (.*)$/gm, "<blockquote>$1</blockquote>");
    escaped = escaped.replace(/(?:^|\n)- (.*?)(?=\n|$)/g, "<li>$1</li>");
    escaped = escaped.replace(/(<li>[\s\S]*?<\/li>)/g, "<ul>$1</ul>");
    escaped = escaped.replace(/\*\*(.*?)\*\*/g, "<strong>$1</strong>");
    escaped = escaped.replace(/~~(.*?)~~/g, "<del>$1</del>");
    escaped = escaped.replace(/\*(.*?)\*/g, "<em>$1</em>");
    escaped = escaped.replace(/`([^`]+)`/g, "<code>$1</code>");
    escaped = escaped.replace(/\[([^\]]+)\]\((https?:\/\/[^)]+)\)/g, '<a href="$2" target="_blank" rel="noreferrer noopener">$1</a>');
    escaped = escaped.replace(/(^|\s)@([A-Za-z0-9_]{3,32})/g, '$1<span class="forum-mention">@$2</span>');
    escaped = escaped.replace(/\n\n+/g, "</p><p>");
    escaped = "<p>" + escaped.replace(/\n/g, "<br>") + "</p>";

    escaped = escaped.replace(/%%CODEBLOCK(\d+)%%/g, function (_, idx) {
      return codeBlocks[Number(idx)] || "";
    });

    return escaped;
  }

  function escapeHtml(value) {
    return value
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;")
      .replace(/'/g, "&#39;");
  }

  function escapeRegExp(value) {
    return value.replace(/[.*+?^${}()|[\]\\]/g, "\\$&");
  }

  document.addEventListener("DOMContentLoaded", init);
})();
