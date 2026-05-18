<?php
// footer.php
?>

<script>
setInterval(() => {
    fetch('heartbeat.php', {
        method: 'POST',
        credentials: 'same-origin'
    });
}, 60000);
</script>

</body>
</html>
