# កិច្ចការវាយតម្លៃ — Assignment Assessment Criteria P19
## Web App Framework (វិទ្យាសាស្ត្រកម្មវិធីគេហទំព័រ) — Full Explanation & Checklist

> **Source:** `Assignment Assessment Criteria P19 (4).pdf` (8 pages)
> **Explained for:** Team **Arizu Arimato** — Hotel Management System
> **Total: 100 points**

---

# PART 1 — What This Assignment Is

## 1.1 General Rules (From Page 1)

| Rule | Detail |
|------|--------|
| **Course** | Web App Framework |
| **Task** | Build a complete web application project |
| **Team size** | **5 members per group** ⚠️ |
| **Timeline** | From the planning phase until the end of the semester |
| **Preferred language** | **PHP** or **Laravel framework** (other languages/frameworks allowed if approved by the teacher) |
| **Method** | Must follow a standard software development process (SDLC) |

> ⚠️ **IMPORTANT for your team:** The document says **"ក្នុងក្រុមមានសមាជិក ៥នាក់"** = *each group has **5 members***.
> Your GitHub currently shows **4 members**. **Ask your teacher** whether you must recruit a 5th member.

---

# PART 2 — The 10 Assessment Criteria (100 Points)

| # | Criterion | Points | Weight |
|---|-----------|:------:|:------:|
| 1 | Project Proposal & Planning | **10** | 🟦🟦 |
| 2 | Requirements Analysis | **10** | 🟦🟦 |
| 3 | System Design | **10** | 🟦🟦 |
| 4 | **Implementation (Coding)** | **20** | 🟦🟦🟦🟦 ⭐ *biggest* |
| 5 | Testing | **5** | 🟦 |
| 6 | Deployment | **10** | 🟦🟦 |
| 7 | Documentation | **5** | 🟦 |
| 8 | Presentation & Demonstration | **10** | 🟦🟦 |
| 9 | Teamwork & Individual Contribution | **10** | 🟦🟦 |
| 10 | Create Video | **10** | 🟦🟦 |
| | **TOTAL** | **100** | |

---

## 📌 Criterion 1 — Project Proposal & Planning (10 pts)

**Khmer:** បង្កើតផែនការគម្រោងសិក្សា — *Create the project plan*

You must document:

| Item | Khmer | Meaning |
|------|-------|---------|
| ចំណុចបញ្ហា | Problem statement | **What problem does your app solve?** e.g. *"Hotels still use paper records → bookings are lost, double-booked, and reporting is slow."* |
| គោលបំណង | Objectives | What the system will achieve (main goal + 3–5 sub-goals) |
| ដែលកំណត់វិធីសាស្រ្ត | Scope / method | What's inside the system, what's excluded, which method you use |
| តួនាទីភារកិច្ចសមាជិកក្រុម | Team roles | Who does what — split fairly and clearly |

**✅ Deliverables to prepare:** `PROPOSAL.md` (or Word/PDF) containing problem, objectives, scope, timeline (Gantt), and a role table.

---

## 📌 Criterion 2 — Requirements Analysis (10 pts)

Two kinds of requirements must be written down:

### Functional Requirements (មុខងាររបស់ប្រព័ន្ធ)
List every feature. Examples from the PDF: User Registration, Login, Dashboard, CRUD, Search, Reports.

**For your hotel project:**
- ✅ Public site: view rooms, search availability by date, online reservation, contact form
- ✅ Admin: login, dashboard, room CRUD, guests, reservations, check-in/out, billing, reports, users, backup/restore

### Non-functional Requirements (មុខងារអភិវឌ្ឍន៍/គុណភាព)

| Requirement | What to write |
|-------------|---------------|
| **Security** | Password hashing, PDO prepared statements (SQL-injection safe), session protection, role-based access |
| **Performance** | Page load targets, indexed queries, caching |
| **Backup** | Automatic DB backup + restore procedure |

> 💡 **Tip:** Present these as two tables — *Functional* and *Non-functional*. Easy marks.

---

## 📌 Criterion 3 — System Design (10 pts)

You must produce **all five**:

| # | Design item | What it shows |
|---|-------------|---------------|
| 1 | **ERD** (Entity Relationship Diagram) | Tables + relationships |
| 2 | **Database Design** | Tables, columns, types, keys, relationships, constraints |
| 3 | **Data Flow Diagrams & Flowchart** | How data moves; step-by-step logic (e.g. booking flow) |
| 4 | **Figma or Wireframes** | UI design mockups *before* coding |
| 5 | **System Architecture** | Client → Frontend → REST API → Backend → Database |

**✅ You already have:** `hotel_erd.html` (ERD), database schema in `backend/database/`.
**⬜ Still needed:** DFD/Flowchart, Figma wireframes, Architecture diagram.

---

## 📌 Criterion 4 — Implementation / Coding (20 pts) ⭐ HIGHEST WEIGHT

| Sub-criterion | What the teacher checks |
|---------------|-------------------------|
| **Functionality** | Does everything actually work? (booking, login, billing, reports) |
| **Code Quality** | Clean structure, naming conventions, folder structure, comments, DRY/SOLID |
| **Security** | Prepared statements, validation, password hashing, access control |
| **Responsive UI** | Works on phone, tablet, desktop |
| **Validation** | Both client-side (JS) and server-side (PHP) input checks |

**Coding standards expected (from page 6):**
- Naming conventions
- Folder structure
- Comments
- Clean Code principles
- SOLID principles
- DRY (Don't Repeat Yourself)

---

## 📌 Criterion 5 — Testing (5 pts)

| Type | What to show |
|------|--------------|
| **Unit Testing** | Test individual functions (e.g. `calcNights()`, price calculation) |
| **Integration Testing** | Test modules together (booking → room availability → billing) |
| **System Testing** | Test the whole app end-to-end |
| **UAT** (if possible) | A real user trying the system |

> Find all bugs, fix them, and **repeat until the system runs smoothly**.
> **✅ Deliverable:** a testing table: *Test case → Steps → Expected → Actual → Pass/Fail*.

---

## 📌 Criterion 6 — Deployment (10 pts)

Put the system online so anyone can open it.

```
Development Server → Testing Server → Production Server
```

**Options:** GitHub Pages, Vercel, Docker, CI/CD, GitHub Actions, GitLab CI.

**✅ You already have:**
- Live demo: `https://kompheakkeo1111-creator.github.io/Frontendsystemhotel/`
- GitHub Actions workflow in `.github/workflows/`
- `FREE_HOSTING.md` + `CLIENT_DEPLOYMENT.md` guides

---

## 📌 Criterion 7 — Documentation (5 pts)

| Type | Required documents |
|------|--------------------|
| **Technical** | API documentation, Database schema, Installation guide |
| **User** | User Manual, Admin Manual |
| **Developer** | Folder structure, Coding standards, Deployment guide |

**✅ You already have (strong):** `README.md`, `CODE_EXPLANATION.md` (53 KB), `CODE_EXPLANATIONS/` (15 files), `CONTRIBUTING.md`, `CLIENT_DEPLOYMENT.md`, `FREE_HOSTING.md`.
**⬜ Suggested:** add a short **User Manual** + **Admin Manual**.

---

## 📌 Criterion 8 — Presentation & Demonstration (10 pts)

| Item | Khmer |
|------|-------|
| Explain each key feature clearly | ការបង្ហាញពន្យល់ចំណុចពិសេសនីមួយៗ |
| **Live Demo** | ការសាកល្បងបង្ហាញផ្ទាល់ |
| Answer questions | ការឆ្លើយសំណួរ |

**✅ Deliverable:** slides + a rehearsed 10-minute demo + one member ready for Q&A.

---

## 📌 Criterion 9 — Teamwork & Individual Contribution (10 pts)

You must **prove** teamwork:

| Requirement | Tools accepted |
|-------------|----------------|
| Show each member's progress/contribution | Git contribution graph, Trello, Jira, Notion |
| **Version Control** | **Git commits, GitHub, GitLab** ✅ |
| Project management board | Trello, Jira, Notion |
| Team communication | Zoom, Google Meet, Discord, Slack |

**✅ You already have:** GitHub repo, branch workflow (`main`/`develop`/`feature/*`), Pull Requests, `CONTRIBUTING.md`.
**⬜ Needed:** screenshots of your **Trello/Notion board** + **chat/meeting evidence** + all 5 members actually pushing commits.

---

## 📌 Criterion 10 — Create Video (10 pts)

**បង្កើតវីដេអូបង្ហាញពីគន្លងនៃដំណើរ Project ទាំងមូល**
= *Create a video presenting the entire project journey.*

**✅ Suggested content (2–5 minutes):**
1. Team intro & problem statement
2. Planning & design (ERD, wireframes)
3. Live demo of the website + admin panel
4. Show GitHub commits/PRs proving everyone contributed
5. Testing & deployment result

> ⬜ **This is 10 points — don't forget it!**

---

# PART 3 — The 9 Development Phases (Pages 3–8 — Teacher's Guideline)

The teacher gives you the recommended process. Follow these phases:

| Phase | Name | What you do |
|:-----:|------|-------------|
| 1 | **Project Proposal & Planning** | Define the problem, objectives, timeline, split roles |
| 2 | **Requirements Analysis** | List functional (Login, CRUD, Search, Reports) + non-functional (Security, Performance, Backup) |
| 3 | **Choose Technology Stack** | Decide frontend / backend / DB / VCS / PM / communication tools |
| 4 | **System Design** | UI design (Figma), Database design (ER, tables, relationships, constraints), System Architecture |
| 5 | **Development Setup** | Install Git, IDE, Database, project dependencies — every member |
| 6 | **Coding Standards** | Naming, folder structure, comments, Clean Code, SOLID, DRY |
| 7 | **Testing** | Unit → Integration → System → UAT; fix all bugs |
| 8 | **Deployment** | Development → Testing → Production (Docker / CI/CD / GitHub Actions) |
| 9 | **Documentation** | Technical (API, schema, install), User (user/admin manual), Developer (folder, standards, deploy) |

## Team Roles (Page 3–4)

| Role | Responsibility |
|------|----------------|
| **Team Leader** | Plans work, reviews and approves code commits |
| **Backend Developer** | Builds APIs, designs database, writes business logic |
| **Frontend Developer** | Designs UI/UX |
| **Full Stack Developer** | Codes the whole project |
| **QA / Test Engineer** | Tests and fixes bugs |

> 💡 *If your group has fewer full-stack members, split work by **modules** instead.*

## Technology Stack Options (Pages 4–5)

| Layer | Choices |
|-------|---------|
| Frontend | React, Vue, Angular |
| Backend | **PHP (Laravel)**, Node.js |
| Database | **MySQL**, PostgreSQL |
| Version Control | **Git**, GitHub / GitLab |
| Project Management | Jira, Trello, Notion |
| Communication | Slack, Discord, Microsoft Teams |

---

# PART 4 — ✅ Score Checklist for OUR Project (Arizu Arimato)

| # | Criterion | Pts | Status | Evidence you already have | What's missing |
|---|-----------|:---:|:------:|---------------------------|----------------|
| 1 | Proposal & Planning | 10 | 🟨 Partial | `README.md`, `CONTRIBUTING.md` | ❌ Formal proposal: problem, objectives, timeline, **role table** |
| 2 | Requirements Analysis | 10 | 🟨 Partial | Features listed in README | ❌ Formal functional + non-functional tables |
| 3 | System Design | 10 | 🟨 Partial | ✅ `hotel_erd.html`, DB schema | ❌ DFD/Flowchart, ❌ Figma wireframes, ❌ Architecture diagram |
| 4 | Implementation | 20 | 🟢 Strong | Full PHP MVC + responsive UI + validation | Keep quality high; add comments |
| 5 | Testing | 5 | 🔴 Weak | — | ❌ No test docs yet (unit/integration/system) |
| 6 | Deployment | 10 | 🟢 Strong | ✅ GitHub Pages live demo + Actions workflow | ✅ Almost done |
| 7 | Documentation | 5 | 🟢 Strong | ✅ 15 `CODE_EXPLANATIONS/` + README + guides | ❌ User Manual / Admin Manual |
| 8 | Presentation | 10 | 🔴 Not started | — | ❌ Slides + rehearsed live demo + Q&A |
| 9 | Teamwork | 10 | 🟨 Partial | ✅ GitHub, branches, PRs, `CONTRIBUTING.md` | ❌ Trello/Notion board, ❌ chat evidence, ❌ member commits |
| 10 | Create Video | 10 | 🔴 Not started | — | ❌ Project video |
| | **TOTAL** | **100** | | | |

### 🎯 Highest-value actions (by points)

1. **20 pts — Implementation:** keep polishing features & code quality
2. **10 pts — Video:** record it — easy marks, often forgotten
3. **10 pts — Teamwork:** get **all members pushing real commits** + a Trello/Notion board
4. **10 pts — Presentation:** slides + live demo rehearsal
5. **10 pts — Design:** add DFD, wireframes, architecture diagram
6. **10 pts — Proposal & Requirements:** write the two documents
7. **10 pts — Deployment:** confirm live link works
8. **5+5 — Testing & User Manuals:** small effort, guaranteed marks

---

# PART 5 — Quick Summary (អត្ថប្រយោជន៍សង្ខេប)

> Build a **team web project** (5 members), prefer **PHP/Laravel**, follow the **9 SDLC phases**,
> and get judged on **10 criteria worth 100 points**:
> **Planning 10 · Requirements 10 · Design 10 · Coding 20 · Testing 5 · Deployment 10 · Docs 5 · Presentation 10 · Teamwork 10 · Video 10**
>
> Proof of teamwork = **real Git commits on GitHub + project board + team communication**.

---

*File generated: 2026-10-22 · Source: Assignment Assessment Criteria P19 (4).pdf*
*Companion files: `README.md`, `CONTRIBUTING.md`, `CODE_EXPLANATION.md`, `CODE_EXPLANATIONS/`*
