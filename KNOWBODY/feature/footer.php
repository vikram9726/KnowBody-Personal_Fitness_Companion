<style>

.footer {
    width: 82%;
    position: fixed;
    bottom: 0;
    left: 18%;
    z-index: 500;
    border-radius:40%;
}
}

.footer .glass-container {
    width: 100%;
    text-align: center;
    background: rgba(255, 255, 255, 0.15);
    backdrop-filter: blur(10px);
    padding: 5px 0;
    color: white;
    border-top: 1px solid rgba(255, 255, 255, 0.2);
    border-radius:20%;
}
</style>


<footer class="footer">
    <div class="glass-container">
        <p>&copy; <?php echo date('Y'); ?> KnowBody. All rights reserved.</p>
    </div>
</footer>
