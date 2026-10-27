using System;
using System.Collections.Generic;
using System.Net.Http;
using System.Text.Json;
using System.Threading.Tasks;
using PubQuizApp.Models;

namespace PubQuizApp.Services
{
    public class QuizApiService
    {
        private readonly HttpClient _client;

        public QuizApiService()
        {
            _client = new HttpClient
            {
                Timeout = TimeSpan.FromSeconds(8)
            };
        }

        public async Task<List<QuizSummary>> GetQuizzesAsync(string baseUrl)
        {
            try {
                var url = $"{baseUrl.TrimEnd('/')}/api/quizzes.php";
                var json = await _client.GetStringAsync(url);
                var response = JsonSerializer.Deserialize<ApiResponse<List<QuizSummary>>>(json);
                if (response?.Status == "success" && response.Data != null)
                {
                    return response.Data;
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"API GetQuizzes Exception: {ex.Message}");
            }

            // Return fallback sample list if server unreachable
            return GetOfflineSummaryList();
        }

        public async Task<QuizDetail?> GetQuizDetailAsync(string baseUrl, int quizId)
        {
            try {
                var url = $"{baseUrl.TrimEnd('/')}/api/quiz.php?id={quizId}";
                var json = await _client.GetStringAsync(url);
                var response = JsonSerializer.Deserialize<ApiResponse<QuizDetail>>(json);
                if (response?.Status == "success" && response.Data != null)
                {
                    return response.Data;
                }
            }
            catch (Exception ex)
            {
                System.Diagnostics.Debug.WriteLine($"API GetQuizDetail Exception: {ex.Message}");
            }

            // Return fallback sample quiz if server unreachable
            return GetOfflineDemoQuiz();
        }

        private List<QuizSummary> GetOfflineSummaryList()
        {
            return new List<QuizSummary>
            {
                new QuizSummary
                {
                    Id = 1,
                    Title = "Offline Demo: Pop Culture Pub Quiz",
                    Category = "Music & Movies",
                    Description = "Fallback sample quiz (PHP Server Offline)",
                    DefaultTimer = 25,
                    QuestionCount = 3
                }
            };
        }

        private QuizDetail GetOfflineDemoQuiz()
        {
            return new QuizDetail
            {
                Id = 1,
                Title = "Offline Demo: Pop Culture Pub Quiz",
                Category = "Music & Movies",
                Description = "Fallback sample quiz running without PHP server connection.",
                DefaultTimer = 25,
                Questions = new List<Question>
                {
                    new Question
                    {
                        Id = 101,
                        QuestionText = "Which legendary band performed the famous hit 'Bohemian Rhapsody'?",
                        ImageUrl = "https://images.unsplash.com/photo-1511671782779-c97d3d27a1d4?w=600&auto=format&fit=crop",
                        Points = 10,
                        OrderNum = 1,
                        Explanation = "Queen released Bohemian Rhapsody in 1975 on their album A Night at the Opera.",
                        Answers = new List<AnswerChoice>
                        {
                            new AnswerChoice { Id = 1, AnswerText = "The Beatles", IsCorrect = false },
                            new AnswerChoice { Id = 2, AnswerText = "Queen", IsCorrect = true },
                            new AnswerChoice { Id = 3, AnswerText = "Pink Floyd", IsCorrect = false },
                            new AnswerChoice { Id = 4, AnswerText = "Led Zeppelin", IsCorrect = false }
                        }
                    },
                    new Question
                    {
                        Id = 102,
                        QuestionText = "What is the highest-grossing film of all time (unadjusted for inflation)?",
                        Points = 10,
                        OrderNum = 2,
                        Explanation = "Avatar (2009) directed by James Cameron remains #1 worldwide!",
                        Answers = new List<AnswerChoice>
                        {
                            new AnswerChoice { Id = 11, AnswerText = "Avengers: Endgame", IsCorrect = false },
                            new AnswerChoice { Id = 12, AnswerText = "Titanic", IsCorrect = false },
                            new AnswerChoice { Id = 13, AnswerText = "Avatar", IsCorrect = true },
                            new AnswerChoice { Id = 14, AnswerText = "Star Wars", IsCorrect = false }
                        }
                    }
                }
            };
        }
    }
}
