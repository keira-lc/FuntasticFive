const express = require("express"); 
const app = express(); 
const PORT = process.env.PORT || 3000; 

// Serve static files (like index.html, CSS, images)
app.use(express.static(__dirname)); 

app.get("/", (req, res) => {
  res.sendFile(__dirname + "/index.html");
});

app.listen(PORT, () => {
  console.log(`Server running at http://localhost:${PORT}`);
});