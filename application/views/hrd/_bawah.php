</div>
<script>
document.addEventListener('click', function (e) {
  var t = e.target.closest('[data-konfirmasi]');
  if (t && !confirm(t.getAttribute('data-konfirmasi'))) e.preventDefault();
});
</script>
